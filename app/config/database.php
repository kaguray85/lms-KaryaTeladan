<?php
require_once __DIR__ . '/app.php';

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $host = (string) env('DB_HOST', 'localhost');
            $port = (string) env('DB_PORT', '3306');
            $database = (string) env('DB_DATABASE', 'lms_smk_karya_teladan');
            $username = (string) env('DB_USERNAME', 'root');
            $password = (string) env('DB_PASSWORD', '');
            $charset = (string) env('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

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
