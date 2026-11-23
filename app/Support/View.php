<?php

declare(strict_types=1);

namespace ELabore\Support;

use Throwable;

final class View
{
    /**
     * @param array<string, mixed> $dados
     */
    public static function render(string $view, array $dados = []): string
    {
        $base = dirname(__DIR__) . '/Views/';
        $layout = (string) ($dados['layout'] ?? 'app');
        $conteudo = self::capturar($base . $view . '.php', $dados);

        return self::capturar($base . 'layouts/' . $layout . '.php', $dados + ['content' => $conteudo]);
    }

    /**
     * @param array<string, mixed> $dados
     */
    private static function capturar(string $arquivo, array $dados): string
    {
        extract($dados, EXTR_SKIP);
        ob_start();

        try {
            require $arquivo;
        } catch (Throwable $erro) {
            ob_end_clean();

            throw $erro;
        }

        return (string) ob_get_clean();
    }
}
