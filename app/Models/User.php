<?php

declare(strict_types=1);

namespace ELabore\Models;

use InvalidArgumentException;
use PDO;
use PDOException;

final class User
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array{id:int, name:string, email:string, role:string}|null */
    public function authenticate(string $email, string $password): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        if (!is_array($user) || !password_verify($password, (string) $user['senha_hash'])) {
            return null;
        }

        return $this->publicIdentity($user);
    }

    public function create(string $name, string $email, string $password, string $role): int
    {
        $name = trim($name);
        $email = strtolower(trim($email));
        $role = trim($role);
        $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);

        if ($name === '' || $nameLength > 255) {
            throw new InvalidArgumentException('O nome deve ter entre 1 e 255 caracteres.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            throw new InvalidArgumentException('Informe um e-mail válido com até 255 caracteres.');
        }
        if (strlen($password) < 10 || strlen($password) > 72) {
            throw new InvalidArgumentException('A senha deve ter entre 10 e 72 bytes.');
        }
        if (!in_array($role, ['aluno', 'professor'], true)) {
            throw new InvalidArgumentException('O perfil deve ser aluno ou professor.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash, perfil) '
            . 'VALUES (:name, :email, :password_hash, :role)'
        );

        try {
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
            ]);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw new InvalidArgumentException('Este e-mail já está cadastrado.', 0, $exception);
            }

            throw $exception;
        }

        return (int) $this->connection->lastInsertId();
    }

    /** @param array<string, mixed> $user
     *  @return array{id:int, name:string, email:string, role:string}
     */
    private function publicIdentity(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['nome'],
            'email' => (string) $user['email'],
            'role' => (string) $user['perfil'],
        ];
    }
}
