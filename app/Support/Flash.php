<?php

declare(strict_types=1);

namespace ELabore\Support;

/**
 * Mensagens de retorno (sucesso/erro) e campos digitados que sobrevivem a um redirecionamento.
 */
final class Flash
{
    /** @var array<string, mixed>|null */
    private static ?array $antigos = null;

    public static function add(string $tipo, string $mensagem): void
    {
        $_SESSION['_flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }

    /**
     * @return list<array{tipo: string, mensagem: string}>
     */
    public static function pull(): array
    {
        $mensagens = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return is_array($mensagens) ? $mensagens : [];
    }

    /**
     * @param array<string, mixed> $campos
     */
    public static function guardarCampos(array $campos): void
    {
        foreach (array_keys($campos) as $chave) {
            if (str_contains((string) $chave, 'senha') || $chave === '_csrf') {
                unset($campos[$chave]);
            }
        }

        $_SESSION['_old'] = $campos;
    }

    public static function old(string $chave, mixed $padrao = ''): mixed
    {
        if (self::$antigos === null) {
            self::$antigos = is_array($_SESSION['_old'] ?? null) ? $_SESSION['_old'] : [];
            unset($_SESSION['_old']);
        }

        return self::$antigos[$chave] ?? $padrao;
    }
}
