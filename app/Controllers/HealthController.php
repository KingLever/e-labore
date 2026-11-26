<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use Throwable;

final class HealthController
{
    /**
     * Informa se a aplicação consegue abrir uma conexão com o banco.
     *
     * @return array{status: int, body: array<string, string>}
     */
    public function show(): array
    {
        try {
            \createDatabaseConnection()->query('SELECT 1')->fetchColumn();

            return [
                'status' => 200,
                'body' => [
                    'status' => 'ok',
                    'database' => 'connected',
                ],
            ];
        } catch (Throwable $exception) {
            error_log(sprintf('Falha na verificação do banco de dados: %s', $exception->getMessage()));

            return [
                'status' => 503,
                'body' => [
                    'status' => 'error',
                    'message' => 'Serviço temporariamente indisponível.',
                ],
            ];
        }
    }
}
