<?php
require_once __DIR__ . '/app.php';

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $host = 'localhost';
            $database = 'lms_smk_karya_teladan';
            $username = 'root';
            $password = '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$connection = new PDO($dsn, $username, $password, $options);
        }

        return self::$connection;
    }
}
