<?php

declare(strict_types=1);

namespace ELabore\Models;

use PDO;

final class Usuario
{
    public function __construct(private readonly PDO $pdo) {}

    public function autenticar(string $email, string $senha): ?array
    {
        if (strlen($senha) > 72 || str_contains($senha, "\0")) return null;
        $consulta = $this->pdo->prepare('SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = ? LIMIT 1');
        $consulta->execute([$email]);
        $usuario = $consulta->fetch();
        if (!is_array($usuario) || !password_verify($senha, (string) $usuario['senha_hash'])) return null;
        unset($usuario['senha_hash']);
        return $usuario;
    }

    public function cadastrarAluno(string $nome, string $email, string $senha): void
    {
        $this->pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?)')
            ->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), 'aluno']);
    }
}
