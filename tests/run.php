<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$controllerClass = \ELabore\Controllers\HealthController::class;

if (!class_exists($controllerClass)) {
    fwrite(STDERR, "FAIL: HealthController deve estar disponível para verificar a rota de saúde.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/database.php';

use ELabore\Controllers\HealthController;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

function assertDoesNotContain(string $needle, string $haystack, string $message): void
{
    if (str_contains($haystack, $needle)) {
        throw new RuntimeException($message);
    }
}

function runTest(string $name, callable $test): bool
{
    try {
        $test();
        fwrite(STDOUT, "PASS: {$name}\n");
        return true;
    } catch (Throwable $exception) {
        fwrite(STDERR, "FAIL: {$name} — {$exception->getMessage()}\n");
        return false;
    }
}

$passed = 0;
$failed = 0;

if (runTest('configuração ausente retorna resposta genérica 503', static function (): void {
    $_ENV = [
        'DB_HOST' => '',
        'DB_PORT' => '3306',
        'DB_NAME' => 'elabore_db',
        'DB_USER' => 'aluno',
        'DB_PASSWORD' => '',
    ];

    $response = (new HealthController())->show();

    assertSameValue(503, $response['status'], 'A configuração ausente deve retornar status 503.');
    assertSameValue(
        ['status' => 'error', 'message' => 'Serviço temporariamente indisponível.'],
        $response['body'],
        'O corpo deve ser genérico e estável.'
    );
})) {
    $passed++;
} else {
    $failed++;
}

if (runTest('falha de conexão não expõe credenciais na resposta', static function (): void {
    $_ENV = [
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '1',
        'DB_NAME' => 'sensitive_database',
        'DB_USER' => 'sensitive_user',
        'DB_PASSWORD' => 'sensitive_password',
    ];

    $response = (new HealthController())->show();
    $encodedBody = json_encode($response['body'], JSON_THROW_ON_ERROR);

    assertSameValue(503, $response['status'], 'Falha ao conectar deve retornar status 503.');
    assertSameValue(
        ['status' => 'error', 'message' => 'Serviço temporariamente indisponível.'],
        $response['body'],
        'A falha de conexão deve usar o mesmo corpo genérico.'
    );
    assertDoesNotContain('sensitive_database', $encodedBody, 'A resposta não pode expor o banco.');
    assertDoesNotContain('sensitive_user', $encodedBody, 'A resposta não pode expor o usuário.');
    assertDoesNotContain('sensitive_password', $encodedBody, 'A resposta não pode expor a senha.');
})) {
    $passed++;
} else {
    $failed++;
}

fwrite(STDOUT, sprintf("Resultado: %d passaram; %d falharam.\n", $passed, $failed));
exit($failed === 0 ? 0 : 1);
