<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$projectRoot = dirname(__DIR__);

require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/config/database.php';

Dotenv::createImmutable($projectRoot)->safeLoad();

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($requestUri, PHP_URL_PATH);
$path = is_string($path) && $path !== '' ? $path : '/';

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
