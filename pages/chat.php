<?php
session_start();

$botToken = getenv('TELEGRAM_BOT_TOKEN') ?: '7495186187:AAEybjmCrMY78Me6jqCa7SmKuETHAp6iMtk';
$chatId = getenv('TELEGRAM_CHAT_ID') ?: '-5007743123';
$botLink = getenv('TELEGRAM_BOT_LINK') ?: '';
$hasBotConfig = ($botToken !== '' && $chatId !== '');

if (!isset($_SESSION['asesor_chat'])) {
    $_SESSION['asesor_chat'] = [];
}
if (!isset($_SESSION['tg_offset'])) {
    $_SESSION['tg_offset'] = 0;
}

/**
 * Envía una petición al API de Telegram y devuelve un arreglo con ok/description/result.
 */
function tg_request($token, $method, array $data)
{
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    $context = stream_context_create([
        'http' => [
            'header'        => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'        => 'POST',
            'content'       => http_build_query($data),
            'timeout'       => 10,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents($url, false, $context);
    if ($resp === false) {
        $err = error_get_last();
        return ['ok' => false, 'description' => $err['message'] ?? 'No se pudo conectar'];
    }

    $decoded = json_decode($resp, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'description' => 'Respuesta no válida de Telegram'];
    }

    return [
        'ok'          => $decoded['ok'] ?? false,
        'description' => $decoded['description'] ?? null,
        'result'      => $decoded['result'] ?? null,
        'raw'         => $decoded,
    ];
}

/**
 * Guarda un mensaje en la sesión para mostrarlo en pantalla.
 */
function store_message($role, $text)
{
    $clean = trim((string) $text);
    if ($clean === '') {
        return;
    }

    $_SESSION['asesor_chat'][] = [
        'role' => $role,
        'text' => $clean,
        'time' => time(),
    ];
    $_SESSION['asesor_chat'] = array_slice($_SESSION['asesor_chat'], -80);
}

/**
 * Lee nuevos mensajes del bot (respuestas del asesor en Telegram).
 */
function pull_telegram_updates($token, $chatId)
{
    $offset = isset($_SESSION['tg_offset']) ? (int) $_SESSION['tg_offset'] : 0;
    $params = ['timeout' => 1];
    if ($offset > 0) {
        $params['offset'] = $offset + 1;
    }

    $resp = tg_request($token, 'getUpdates', $params);
    if (!is_array($resp) || $resp['ok'] !== true || !is_array($resp['result'])) {
        return;
    }

    $lastId = $offset;
    foreach ($resp['result'] as $update) {
        if (!isset($update['update_id'])) {
            continue;
        }
        $lastId = max($lastId, (int) $update['update_id']);

        $message = $update['message'] ?? null;
        if (!$message) {
            continue;
        }
        $incomingChatId = (string) ($message['chat']['id'] ?? '');
        if ($incomingChatId !== (string) $chatId) {
            continue;
        }

        $text = $message['text'] ?? '';
        if ($text !== '') {
            store_message('asesor', $text);
        }
    }

    $_SESSION['tg_offset'] = $lastId;
}

/**
 * Devuelve JSON estándar para las peticiones AJAX del chat.
 */
function chat_response($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'send') {
        $message = isset($_POST['message']) ? trim((string) $_POST['message']) : '';
        if ($message === '') {
            chat_response(['ok' => false, 'error' => 'Escribe un mensaje antes de enviar.'], 400);
        }

        store_message('yo', $message);

        $delivery = null;
        if ($hasBotConfig) {
            $context = 'Cliente web #' . substr(session_id(), 0, 6);
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'sin ip';
            $payload = [
                'chat_id' => $chatId,
                'text'    => $context . " (" . $ip . "): " . $message,
            ];
            $delivery = tg_request($botToken, 'sendMessage', $payload);
        }

        chat_response([
            'ok'          => true,
            'chat'        => $_SESSION['asesor_chat'],
            'sent'        => $delivery,
            'configReady' => $hasBotConfig,
        ]);
    }

    if ($action === 'poll') {
        if ($hasBotConfig) {
            pull_telegram_updates($botToken, $chatId);
        }

        chat_response([
            'ok'          => true,
            'chat'        => $_SESSION['asesor_chat'],
            'configReady' => $hasBotConfig,
        ]);
    }

    chat_response(['ok' => false, 'error' => 'Acción no soportada'], 400);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <title>Asesor en línea</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/bg-lineas.css">
    <script defer src="/js/no-zoom.js"></script>
    <style>
        :root {
            --bg:#0f172a;
            --card:#0b1223;
            --accent:#4ade80;
            --muted:#94a3b8;
            --line:#1e293b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at 20% 20%, #1e3a8a 0, rgba(30,58,138,0) 32%), radial-gradient(circle at 80% 10%, #0ea5e9 0, rgba(14,165,233,0) 30%), var(--bg);
            color: #e2e8f0;
            font-family: 'Manrope', system-ui, -apple-system, 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }
        .shell {
            width: 100%;
            max-width: 960px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.45);
            overflow: hidden;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(120deg, rgba(74,222,128,0.12), rgba(14,165,233,0.08));
        }
        .title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 14px;
        }
        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .btn {
            appearance: none;
            border: 0;
            border-radius: 14px;
            padding: 12px 18px;
            font-weight: 700;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
            color: #0f172a;
            background: #e2e8f0;
            box-shadow: 0 12px 30px rgba(74,222,128,0.25);
        }
        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: translateY(0); }
        .btn-ghost {
            background: rgba(226,232,240,0.12);
            color: #e2e8f0;
            box-shadow: none;
        }
        .chat {
            padding: 20px 22px;
            max-height: 70vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .bubble {
            max-width: 80%;
            padding: 12px 14px;
            border-radius: 14px;
            line-height: 1.5;
            position: relative;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .bubble.me {
            align-self: flex-end;
            background: #1e293b;
            border: 1px solid #334155;
        }
        .bubble.asesor {
            align-self: flex-start;
            background: #0ea5e9;
            color: #0b1223;
        }
        .time {
            display: block;
            margin-top: 6px;
            color: rgba(226,232,240,0.7);
            font-size: 11px;
        }
        .composer {
            border-top: 1px solid var(--line);
            padding: 14px 22px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
            background: rgba(15,23,42,0.8);
        }
        .input {
            width: 100%;
            border: 1px solid var(--line);
            background: #0b1223;
            color: #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 15px;
            outline: none;
            transition: border .12s ease, box-shadow .12s ease;
        }
        .input:focus {
            border-color: rgba(74,222,128,0.6);
            box-shadow: 0 0 0 3px rgba(74,222,128,0.12);
        }
        .alert {
            padding: 10px 12px;
            background: rgba(234,179,8,0.12);
            color: #fcd34d;
            border: 1px solid rgba(234,179,8,0.25);
            border-radius: 12px;
            margin: 12px 22px 0;
            font-size: 13px;
        }
        @media (max-width: 640px) {
            .shell { border-radius: 16px; }
            .header { flex-direction: column; align-items: flex-start; gap: 8px; }
            .actions { width: 100%; }
            .actions .btn { width: 100%; text-align: center; }
            .bubble { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="header">
            <div>
                <p class="title">Asesor en línea</p>
                <p class="subtitle">Escribe aquí y recibe la respuesta por Telegram, reflejada en esta página.</p>
            </div>
            <div class="actions">
                <button class="btn-ghost btn" id="refresh">Actualizar</button>
                <a class="btn" id="tgLink" target="_blank" rel="noopener">Abrir en Telegram</a>
            </div>
        </div>

        <?php if (!$hasBotConfig): ?>
            <div class="alert">
                Configura las variables <code>TELEGRAM_BOT_TOKEN</code> y <code>TELEGRAM_CHAT_ID</code> (y opcionalmente <code>TELEGRAM_BOT_LINK</code>) para que el chat envíe y lea mensajes reales desde tu bot.
            </div>
        <?php endif; ?>

        <div class="chat" id="chat"></div>

        <form class="composer" id="chatForm">
            <input type="text" name="message" id="message" class="input" placeholder="Escribe tu mensaje..." autocomplete="off" required>
            <button type="submit" class="btn">Enviar</button>
        </form>
    </div>

    <script>
        const chatBox = document.getElementById('chat');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('message');
        const refreshBtn = document.getElementById('refresh');
        const tgLink = document.getElementById('tgLink');
        const botHref = <?php echo json_encode($botLink ?: ''); ?>;
        if (botHref) {
            tgLink.href = botHref;
        } else {
            tgLink.href = '#';
            tgLink.addEventListener('click', (e) => e.preventDefault());
            tgLink.title = 'Agrega TELEGRAM_BOT_LINK para habilitar el enlace.';
        }

        function renderChat(items) {
            chatBox.innerHTML = '';
            (items || []).forEach(msg => {
                const bubble = document.createElement('div');
                bubble.className = 'bubble ' + (msg.role === 'yo' ? 'me' : 'asesor');
                bubble.textContent = msg.text;
                const time = document.createElement('span');
                time.className = 'time';
                const date = new Date((msg.time || 0) * 1000);
                time.textContent = date.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                bubble.appendChild(time);
                chatBox.appendChild(bubble);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        async function postMessage(message) {
            const data = new FormData();
            data.append('message', message);
            const res = await fetch('chat.php?action=send', { method: 'POST', body: data });
            return res.json();
        }

        async function pollChat() {
            try {
                const res = await fetch('chat.php?action=poll');
                const data = await res.json();
                if (data.chat) {
                    renderChat(data.chat);
                }
            } catch (err) {
                console.error(err);
            }
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const value = input.value.trim();
            if (!value) return;
            input.value = '';
            const resp = await postMessage(value);
            if (resp.chat) {
                renderChat(resp.chat);
            }
        });

        refreshBtn.addEventListener('click', pollChat);
        pollChat();
        setInterval(pollChat, 5000);
    </script>
</body>
</html>


