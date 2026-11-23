<?php

declare(strict_types=1);

use Dotenv\Dotenv;

/**
 * Carrega as variáveis locais sem deixar erros do arquivo .env chegarem à resposta HTTP.
 * Se o pacote vlucas/phpdotenv não estiver instalado (projeto rodando sem Composer), lê o .env com um leitor simples.
 */
function loadEnvironmentConfiguration(string $projectRoot): bool
{
    try {
        if (class_exists(Dotenv::class)) {
            Dotenv::createImmutable($projectRoot)->safeLoad();

            return true;
        }

        $file = $projectRoot . '/.env';

        if (!is_file($file)) {
            return true;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[0] === $value[-1]) {
                $value = substr($value, 1, -1);
            }

            if ($name !== '' && !array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
            }
        }

        return true;
    } catch (\Throwable $exception) {
        error_log(sprintf('Falha ao carregar a configuração do ambiente: %s', $exception->getMessage()));

        return false;
    }
}
