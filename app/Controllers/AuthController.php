<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Support\Auth;
use ELabore\Support\Db;
use ELabore\Support\Flash;
use ELabore\Support\Response;
use PDOException;

final class AuthController
{
    /** @return array<string, mixed> */
    public function home(): array
    {
        $usuario = Auth::usuario();

        if ($usuario === null) {
            return Response::redirect('/login');
        }

        return Response::redirect(Auth::inicio($usuario['perfil']));
    }

    /** @return array<string, mixed> */
    public function showLogin(): array
    {
        if (Auth::usuario() !== null) {
            return $this->home();
        }

        return Response::view('auth/login', ['layout' => 'guest', 'titulo' => 'Entrar']);
    }

    /** @return array<string, mixed> */
    public function login(): array
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');

        $consulta = Db::pdo()->prepare('SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = ? LIMIT 1');
        $consulta->execute([$email]);
        $usuario = $consulta->fetch();

        if (!is_array($usuario) || !password_verify($senha, (string) $usuario['senha_hash'])) {
            Flash::guardarCampos(['email' => $email]);

            return Response::redirect('/login', 'danger', 'Email ou senha inválidos.');
        }

        Auth::entrar($usuario);

        return Response::redirect(Auth::inicio((string) $usuario['perfil']));
    }

    /** @return array<string, mixed> */
    public function showRegister(): array
    {
        if (Auth::usuario() !== null) {
            return $this->home();
        }

        return Response::view('auth/cadastro', ['layout' => 'guest', 'titulo' => 'Cadastro']);
    }

    /** Cadastra uma conta de aluno. Professores são promovidos direto no banco. */
    /** @return array<string, mixed> */
    public function register(): array
    {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');

        $erro = null;

        if ($nome === '' || mb_strlen($nome) > 255) {
            $erro = 'Informe seu nome.';
        } elseif (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um email válido.';
        } elseif (strlen($senha) < 6) {
            $erro = 'A senha precisa ter pelo menos 6 caracteres.';
        }

        if ($erro !== null) {
            Flash::guardarCampos(['nome' => $nome, 'email' => $email]);

            return Response::redirect('/cadastro', 'danger', $erro);
        }

        try {
            $inserir = Db::pdo()->prepare(
                'INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?)'
            );
            $inserir->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), 'aluno']);
        } catch (PDOException $excecao) {
            if ($excecao->getCode() === '23000') {
                Flash::guardarCampos(['nome' => $nome, 'email' => $email]);

                return Response::redirect('/cadastro', 'danger', 'Este email já está cadastrado.');
            }

            throw $excecao;
        }

        return Response::redirect('/login', 'success', 'Cadastro feito! Entre com seu email e senha.');
    }

    /** @return array<string, mixed> */
    public function logout(): array
    {
        Auth::sair();

        return Response::redirect('/login', 'success', 'Você saiu do sistema.');
    }
}
