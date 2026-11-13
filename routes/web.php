<?php

declare(strict_types=1);

use ELabore\Controllers\HealthController;
use ELabore\Controllers\AuthController;
use ELabore\Controllers\ActivityController;

$authentication = new AuthController();
$activities = new ActivityController();

return [
    'GET /health' => [new HealthController(), 'show'],
    'POST /auth/login' => [$authentication, 'login'],
    'GET /auth/me' => [$authentication, 'me'],
    'POST /auth/logout' => [$authentication, 'logout'],
    'GET /activities' => [$activities, 'index'],
    'POST /activities' => [$activities, 'create'],
    'POST /activities/review' => [$activities, 'review'],
];
