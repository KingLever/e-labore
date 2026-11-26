<?php

declare(strict_types=1);

namespace ELabore\Support;

final class Auth
{
    /**
     * @return array{id: int, nome: string, email: string, perfil: string}|null
     */
    public static function usuario(): ?array
    {
        $usuario = $_SESSION['usuario'] ?? null;

        return is_array($usuario) ? $usuario : null;
    }

    /**
     * @param array<string, mixed> $usuario
     */
    public static function entrar(array $usuario): void
    {
        session_regenerate_id(true);
        unset($_SESSION['_csrf']);
        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id'],
            'nome' => (string) $usuario['nome'],
            'email' => (string) $usuario['email'],
            'perfil' => (string) $usuario['perfil'],
        ];
    }

    public static function sair(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function inicio(?string $perfil): string
    {
        return $perfil === 'professor' ? '/professor' : '/aluno';
    }

    /**
     * Garante que há alguém logado com o perfil pedido; senão interrompe com redirecionamento ou 403.
     *
     * @return array{id: int, nome: string, email: string, perfil: string}
     */
    public static function exigir(string $perfil): array
    {
        $usuario = self::usuario();

        if ($usuario === null) {
            throw new HttpException(Response::redirect('/login', 'warning', 'Entre para continuar.'));
        }

        if ($usuario['perfil'] !== $perfil) {
            Response::abort(403);
        }

        return $usuario;
    }
}
