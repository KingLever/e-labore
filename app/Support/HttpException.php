<?php

declare(strict_types=1);

namespace ELabore\Support;

use RuntimeException;

/**
 * Interrompe o controller devolvendo uma resposta pronta (redirecionamento, erro 403/404...).
 */
final class HttpException extends RuntimeException
{
    /**
     * @param array<string, mixed> $response
     */
    public function __construct(public readonly array $response)
    {
        parent::__construct('Resposta HTTP antecipada.');
    }
}
