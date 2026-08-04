<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = file_get_contents('php://input');
    $log = json_decode($content, true);

    $logFile = __DIR__ . '/../logs/err.log';

    $errorMessage = sprintf("[%s] Error: %s\nStack trace:\n%s\n\n", $log['date'], $log['error'], $log['stack']);

    file_put_contents($logFile, $errorMessage, FILE_APPEND);
}

