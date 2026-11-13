<?php

declare(strict_types=1);

use Dotenv\Dotenv;

/**
 * Carrega as variáveis locais sem deixar erros do arquivo .env chegarem à resposta HTTP.
 */
function loadEnvironmentConfiguration(string $projectRoot): bool
{
    try {
        Dotenv::createImmutable($projectRoot)->safeLoad();

        return true;
    } catch (\Throwable $exception) {
        error_log(sprintf('Falha ao carregar a configuração do ambiente: %s', $exception->getMessage()));

        return false;
    }
}
