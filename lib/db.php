<?php

class Database
{
    private static $db;
    private $connection;

    private $servername;
    private $database;
    private $username;
    private $password;

    private function __construct()
    {
        $this->servername = getenv('DB_HOST') ?: 'localhost';
        $this->database  = getenv('DB_DATABASE') ?: 'u389360533_colombia';
        $this->username  = getenv('DB_USERNAME') ?: 'u389360533_colombia';
        $this->password  = getenv('DB_PASSWORD') ?: 'jimenez123S.';

        $this->connectWithRetry();
    }

    private function connectWithRetry()
    {
        mysqli_report(MYSQLI_REPORT_OFF);

        $attempts = 0;
        $maxAttempts = 4;
        $delay = 500000;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $link = mysqli_init();
            if (function_exists('mysqli_options')) {
                @mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
                @mysqli_options($link, MYSQLI_OPT_READ_TIMEOUT, 10);
            }
            $ok = @mysqli_real_connect($link, $this->servername, $this->username, $this->password, $this->database);
            if ($ok) {
                @mysqli_set_charset($link, 'utf8mb4');
                $this->connection = $link;
                return;
            }

            $errno = mysqli_connect_errno();
            if (in_array($errno, [1040, 1226, 2002])) {
                usleep($delay);
                $delay = min($delay * 2, 4000000);
                continue;
            }

            die('Connection failed (' . $errno . '): ' . mysqli_connect_error());
        }
        die('Connection failed after retries: ' . mysqli_connect_error());
    }

    private function ensureAlive()
    {
        if (!$this->connection) {
            $this->connectWithRetry();
            return;
        }
        if (!@mysqli_ping($this->connection)) {
            @mysqli_close($this->connection);
            $this->connectWithRetry();
        }
    }

    public function __destruct()
    {
        if ($this->connection) {
            @mysqli_close($this->connection);
        }
    }

    public static function getConnection()
    {
        if (self::$db === null) {
            self::$db = new Database();
        } else {
            self::$db->ensureAlive();
        }
        return self::$db->connection;
    }
}
