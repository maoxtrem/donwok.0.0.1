<?php

namespace App\Infrastructure\Database;

use PDO;

class PdoConnection
{
    private PDO $pdo;

    public function __construct()
    {
        $url = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;

        if (!$url) {
            throw new \RuntimeException('DATABASE_URL not set');
        }

        $parts = parse_url($url);

        if ($parts === false) {
            throw new \RuntimeException('Invalid DATABASE_URL');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $parts['host'],
            $parts['port'] ?? 3306,
            ltrim($parts['path'], '/')
        );

        $this->pdo = new PDO(
            $dsn,
            $parts['user'] ?? '',
            $parts['pass'] ?? '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function get(): PDO
    {
        return $this->pdo;
    }
}
