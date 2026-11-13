<?php

declare(strict_types=1);

namespace ELabore\Services;

use RuntimeException;

final class SessionManager
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        session_name('ELABORESESSID');

        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new RuntimeException('Não foi possível iniciar a sessão.');
        }
    }

    /** @param array{id:int, name:string, email:string, role:string} $user */
    public function login(array $user): void
    {
        $this->start();

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Não foi possível renovar a sessão.');
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    /** @return array{id:int, name:string, email:string, role:string}|null */
    public function currentUser(): ?array
    {
        $this->start();
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user) || !isset($user['id'], $user['name'], $user['email'], $user['role'])) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
    }

    /** @return array{id:int, name:string, email:string, role:string}|null */
    public function requireRole(string $role): ?array
    {
        $user = $this->currentUser();

        return $user !== null && $user['role'] === $role ? $user : null;
    }

    public function logout(): void
    {
        $this->start();
        $_SESSION = [];
        $cookie = session_get_cookie_params();

        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $cookie['path'],
                'domain' => $cookie['domain'],
                'secure' => $cookie['secure'],
                'httponly' => $cookie['httponly'],
                'samesite' => $cookie['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }
}
