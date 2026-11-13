<?php

declare(strict_types=1);

/**
 * Cria uma conexão PDO com o banco configurado no ambiente.
 */
function createDatabaseConnection(): PDO
{
    $requiredValues = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER'];
    $configuration = [];

    foreach ($requiredValues as $key) {
        $value = trim((string) ($_ENV[$key] ?? ''));

        if ($value === '') {
            throw new RuntimeException(sprintf('A variável %s não foi configurada.', $key));
        }

        $configuration[$key] = $value;
    }

    if (!ctype_digit($configuration['DB_PORT'])) {
        throw new RuntimeException('A variável DB_PORT deve conter uma porta numérica.');
    }

    $port = (int) $configuration['DB_PORT'];

    if ($port < 1 || $port > 65535) {
        throw new RuntimeException('A variável DB_PORT está fora do intervalo permitido.');
    }

    $password = (string) ($_ENV['DB_PASSWORD'] ?? '');
    $dataSourceName = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $configuration['DB_HOST'],
        $port,
        $configuration['DB_NAME']
    );

    return new PDO(
        $dataSourceName,
        $configuration['DB_USER'],
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
