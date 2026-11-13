<?php

declare(strict_types=1);

use ELabore\Controllers\HealthController;

return [
    'GET /health' => [new HealthController(), 'show'],
];
