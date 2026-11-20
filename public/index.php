<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/config/database.php';
require $projectRoot . '/config/environment.php';

if (!loadEnvironmentConfiguration($projectRoot)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(503);
    echo json_encode(
        [
            'status' => 'error',
            'message' => 'Serviço temporariamente indisponível.',
        ],
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    );
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($requestUri, PHP_URL_PATH);
$path = is_string($path) && $path !== '' ? $path : '/';

// Servidor embutido do PHP: entrega as telas .html da pasta public sem passar pelas rotas.
if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . $path);

    if (
        $file !== false
        && is_file($file)
        && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)
        && pathinfo($file, PATHINFO_EXTENSION) === 'html'
    ) {
        return false;
    }
}

$routes = require $projectRoot . '/routes/web.php';
$handler = $routes[sprintf('%s %s', $method, $path)] ?? null;

header('Content-Type: application/json; charset=utf-8');

if (!is_callable($handler)) {
    http_response_code(404);
    $body = [
        'status' => 'error',
        'message' => 'Rota não encontrada.',
    ];
} else {
    $response = $handler();

    if (isset($response['redirect'])) {
        header('Location: ' . $response['redirect']);
        http_response_code($response['status']);
        exit;
    }

    http_response_code($response['status']);
    $body = $response['body'];
}

try {
    echo json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (\JsonException $exception) {
    error_log(sprintf('Falha ao gerar resposta JSON: %s', $exception->getMessage()));
    http_response_code(500);
    echo '{"status":"error","message":"Falha interna."}';
}
