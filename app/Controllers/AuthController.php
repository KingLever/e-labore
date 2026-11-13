<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Models\User;
use ELabore\Services\SessionManager;

final class AuthController
{
    public function __construct(private readonly SessionManager $sessions = new SessionManager())
    {
    }

    /** @return array{status:int, body:array<string, mixed>} */
    public function login(array $input = []): array
    {
        if (!is_string($input['email'] ?? null) || !is_string($input['password'] ?? null)) {
            return $this->response(422, 'Informe e-mail e senha.');
        }

        $email = trim($input['email']);
        $password = $input['password'];

        if ($email === '' || $password === '') {
            return $this->response(422, 'Informe e-mail e senha.');
        }

        $user = (new User(\createDatabaseConnection()))->authenticate($email, $password);
        if ($user === null) {
            return $this->response(401, 'E-mail ou senha inválidos.');
        }

        $this->sessions->login($user);

        return [
            'status' => 200,
            'body' => [
                'status' => 'ok',
                'user' => $user,
            ],
        ];
    }

    /** @return array{status:int, body:array<string, mixed>} */
    public function me(array $input = []): array
    {
        $user = $this->sessions->currentUser();
        if ($user === null) {
            return $this->response(401, 'Entre para continuar.');
        }

        return [
            'status' => 200,
            'body' => [
                'status' => 'ok',
                'user' => $user,
            ],
        ];
    }

    /** @return array{status:int, body:array<string, mixed>} */
    public function logout(array $input = []): array
    {
        $this->sessions->logout();

        return [
            'status' => 200,
            'body' => ['status' => 'ok', 'message' => 'Sessão encerrada.'],
        ];
    }

    /** @return array{status:int, body:array<string, string>} */
    private function response(int $status, string $message): array
    {
        return [
            'status' => $status,
            'body' => ['status' => 'error', 'message' => $message],
        ];
    }
}
