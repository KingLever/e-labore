<?php

declare(strict_types=1);

use ELabore\Controllers\AuthController;
use ELabore\Controllers\HealthController;

return [
    'GET /health' => [new HealthController(), 'show'],
    'POST /cadastro' => [new AuthController(), 'register'],
    'POST /login' => [new AuthController(), 'login'],
    'GET /me' => [new AuthController(), 'me'],
];
