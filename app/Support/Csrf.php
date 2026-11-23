<?php

declare(strict_types=1);

namespace ELabore\Support;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function valido(): bool
    {
        $enviado = (string) ($_POST['_csrf'] ?? '');

        return $enviado !== '' && hash_equals(self::token(), $enviado);
    }
}
