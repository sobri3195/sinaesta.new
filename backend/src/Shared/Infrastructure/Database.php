<?php

declare(strict_types=1);

namespace Sinaesta\Shared\Infrastructure;

use PDO;
use RuntimeException;

final class Database
{
    public static function connectFromEnvironment(): PDO
    {
        $dsn = getenv('DB_DSN');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        if ($dsn === false || $dsn === '' || $user === false) {
            throw new RuntimeException('Database configuration is incomplete.');
        }

        return new PDO($dsn, $user, $password === false ? '' : $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }
}
