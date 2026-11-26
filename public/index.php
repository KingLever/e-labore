<?php

declare(strict_types=1);

use ELabore\Support\Csrf;
use ELabore\Support\Flash;
use ELabore\Support\HttpException;
use ELabore\Support\Response;
use ELabore\Support\View;

$projectRoot = dirname(__DIR__);

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($requestUri, PHP_URL_PATH);
$path = is_string($path) && $path !== '' ? $path : '/';

// Servidor embutido do PHP: entrega CSS, JS e imagens de public/assets sem passar pelas rotas.
if (PHP_SAPI === 'cli-server' && str_starts_with($path, '/assets/')) {
    $file = realpath(__DIR__ . $path);

    if (
        $file !== false
        && is_file($file)
        && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR)
    ) {
        return false;
    }
}

// Sem Composer: se a pasta vendor não existir, usa um carregador simples das classes de app/.
$composerAutoload = $projectRoot . '/vendor/autoload.php';

if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class) use ($projectRoot): void {
        $prefix = 'ELabore\\';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $file = $projectRoot . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

        if (is_file($file)) {
            require $file;
        }
    });
}

require $projectRoot . '/config/database.php';
require $projectRoot . '/config/environment.php';
require $projectRoot . '/app/Support/helpers.php';

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

date_default_timezone_set('America/Sao_Paulo');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

session_name('elabore_sessao');
ini_set('session.use_strict_mode', '1');
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(['path' => '/', 'httponly' => true, 'secure' => $https, 'samesite' => 'Lax']);
session_start();

/**
 * Procura a rota. Aceita {parametro} no caminho (ex.: /professor/turmas/{id}); os valores chegam ao controller como int.
 *
 * @param array<string, callable> $routes
 * @return array{0: callable|null, 1: list<int>}
 */
function findRoute(array $routes, string $method, string $path): array
{
    foreach ($routes as $key => $candidate) {
        [$routeMethod, $routePath] = explode(' ', (string) $key, 2);

        if ($routeMethod !== $method) {
            continue;
        }

        $segments = array_map(
            static fn (string $segment): string => preg_match('/^\{\w+\}$/', $segment) === 1
                ? '(\d+)'
                : preg_quote($segment, '#'),
            explode('/', $routePath)
        );

        if (preg_match('#^' . implode('/', $segments) . '$#', $path, $matches) === 1) {
            return [$candidate, array_map('intval', array_slice($matches, 1))];
        }
    }

    return [null, []];
}

$routes = require $projectRoot . '/routes/web.php';
[$handler, $params] = findRoute($routes, $method, $path);

try {
    if (!is_callable($handler)) {
        Response::abort(404);
    }

    if ($method === 'POST' && !Csrf::valido()) {
        if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $response = Response::voltar(
                '/',
                'danger',
                sprintf('O envio passou do limite do servidor (post_max_size = %s). Envie menos ou menores arquivos.', ini_get('post_max_size'))
            );
        } else {
            $response = Response::voltar('/', 'danger', 'Sua sessão expirou. Tente novamente.');
        }
    } else {
        $response = $handler(...$params);
    }

    if (isset($response['view'])) {
        $response['html'] = View::render($response['view'], $response['data'] ?? []);
    }
} catch (HttpException $exception) {
    $response = $exception->response;

    if (isset($response['view'])) {
        $response['html'] = View::render($response['view'], $response['data'] ?? []);
    }
} catch (Throwable $exception) {
    error_log(sprintf('Erro não tratado: %s em %s:%d', $exception->getMessage(), $exception->getFile(), $exception->getLine()));

    try {
        $response = Response::view('errors/erro', ['titulo' => 'Erro', 'codigo' => 500, 'mensagem' => 'Algo deu errado. Tente novamente em instantes.'], 500);
        $response['html'] = View::render($response['view'], $response['data']);
    } catch (Throwable $inner) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Erro interno.';
        exit;
    }
}

http_response_code((int) $response['status']);

if (isset($response['redirect'])) {
    header('Location: ' . $response['redirect']);
    exit;
}

if (isset($response['html'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo $response['html'];
    exit;
}

if (isset($response['file'])) {
    $file = $response['file'];
    $asciiName = addcslashes((string) preg_replace('/[^\x20-\x7E]/', '_', $file['name']), '"\\');

    header('Content-Type: ' . $file['mime']);
    header('Content-Length: ' . (string) filesize($file['path']));
    header(
        'Content-Disposition: ' . ($file['inline'] ? 'inline' : 'attachment')
        . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($file['name'])
    );
    readfile($file['path']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    echo json_encode($response['body'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (\JsonException $exception) {
    error_log(sprintf('Falha ao gerar resposta JSON: %s', $exception->getMessage()));
    http_response_code(500);
    echo '{"status":"error","message":"Falha interna."}';
}
