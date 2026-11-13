<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/config/database.php';
require $projectRoot . '/config/environment.php';

header('Content-Type: application/json; charset=utf-8');

$sendJson = static function (int $status, array $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    exit;
};

if (!loadEnvironmentConfiguration($projectRoot)) {
    $sendJson(503, [
        'status' => 'error',
        'message' => 'Serviço temporariamente indisponível.',
    ]);
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($requestUri, PHP_URL_PATH);
$path = is_string($path) && $path !== '' ? $path : '/';

try {
    (new \ELabore\Services\SessionManager())->start();
} catch (Throwable $exception) {
    error_log(sprintf('Falha ao iniciar sessão HTTP: %s', $exception->getMessage()));
    $sendJson(500, [
        'status' => 'error',
        'message' => 'Falha interna.',
    ]);
}

$input = [];
if ($method === 'POST') {
    $contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
    if ($contentType !== 'application/json') {
        $sendJson(415, [
            'status' => 'error',
            'message' => 'Envie os dados em formato JSON.',
        ]);
    }

    try {
        $decodedInput = json_decode((string) file_get_contents('php://input'), false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $sendJson(400, [
            'status' => 'error',
            'message' => 'O corpo da requisição contém JSON inválido.',
        ]);
    }

    if (!$decodedInput instanceof stdClass) {
        $sendJson(400, [
            'status' => 'error',
            'message' => 'O corpo da requisição deve ser um objeto JSON.',
        ]);
    }

    $input = get_object_vars($decodedInput);
}

$routes = require $projectRoot . '/routes/web.php';
$handler = $routes[sprintf('%s %s', $method, $path)] ?? null;

if (!is_callable($handler)) {
    $sendJson(404, [
        'status' => 'error',
        'message' => 'Rota não encontrada.',
    ]);
}

try {
    $response = $handler($input);
    if (!isset($response['status'], $response['body']) || !is_array($response['body'])) {
        throw new RuntimeException('O controlador retornou uma resposta inválida.');
    }

    $sendJson((int) $response['status'], $response['body']);
} catch (Throwable $exception) {
    error_log(sprintf('Falha ao processar a requisição HTTP: %s', $exception->getMessage()));
    $sendJson(500, [
        'status' => 'error',
        'message' => 'Falha interna.',
    ]);
}
