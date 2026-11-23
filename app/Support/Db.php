<?php

declare(strict_types=1);

namespace ELabore\Support;

use PDO;

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        return self::$pdo ??= \createDatabaseConnection();
    }
}
