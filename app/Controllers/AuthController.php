<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use PDOException;
use Throwable;

final class AuthController
{
    /**
     * Cadastra um novo usuário com perfil "aluno" e volta para a tela de login.
     *
     * @return array{status: int, redirect: string}
     */
    public function register(): array
    {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');

        if (
            $nome === '' || strlen($nome) > 255
            || strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($senha) < 6
        ) {
            return $this->redirect('/cadastro.html?erro=dados');
        }

        try {
            $statement = \createDatabaseConnection()->prepare(
                'INSERT INTO usuarios (nome, email, senha_hash, perfil)
                 VALUES (:nome, :email, :senha_hash, :perfil)'
            );
            $statement->execute([
                'nome' => $nome,
                'email' => $email,
                'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
                'perfil' => 'aluno',
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return $this->redirect('/cadastro.html?erro=email');
            }

            error_log(sprintf('Falha ao cadastrar usuário: %s', $exception->getMessage()));

            return $this->redirect('/cadastro.html?erro=servidor');
        } catch (Throwable $exception) {
            error_log(sprintf('Falha ao cadastrar usuário: %s', $exception->getMessage()));

            return $this->redirect('/cadastro.html?erro=servidor');
        }

        return $this->redirect('/login.html?cadastro=ok');
    }

    /**
     * Confere email e senha e abre a sessão do usuário.
     *
     * @return array{status: int, redirect: string}
     */
    public function login(): array
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');

        try {
            $statement = \createDatabaseConnection()->prepare(
                'SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $usuario = $statement->fetch();
        } catch (Throwable $exception) {
            error_log(sprintf('Falha ao autenticar usuário: %s', $exception->getMessage()));

            return $this->redirect('/login.html?erro=servidor');
        }

        if (!is_array($usuario) || !password_verify($senha, (string) $usuario['senha_hash'])) {
            return $this->redirect('/login.html?erro=credenciais');
        }

        $this->startSession();
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'perfil' => $usuario['perfil'],
        ];

        // Temporário: até existir uma tela inicial, leva para um gif de gato.
        return $this->redirect('https://cataas.com/cat/gif');
    }

    /**
     * Mostra o usuário logado (rota de teste, até existir uma tela inicial).
     *
     * @return array{status: int, body: array<string, mixed>}
     */
    public function me(): array
    {
        $this->startSession();

        if (!isset($_SESSION['usuario'])) {
            return [
                'status' => 401,
                'body' => ['status' => 'error', 'message' => 'Não autenticado.'],
            ];
        }

        return [
            'status' => 200,
            'body' => ['status' => 'ok', 'usuario' => $_SESSION['usuario']],
        ];
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @return array{status: int, redirect: string}
     */
    private function redirect(string $location): array
    {
        return ['status' => 303, 'redirect' => $location];
    }
}
