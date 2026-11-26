<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Support\Auth;
use ELabore\Support\Db;
use ELabore\Support\Flash;
use ELabore\Support\Response;
use ELabore\Models\Usuario;
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

        $usuario = (new Usuario(Db::pdo()))->autenticar($email, $senha);

        if ($usuario === null) {
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
        } elseif (strlen($senha) < 10 || strlen($senha) > 72 || str_contains($senha, "\0")) {
            $erro = 'A senha precisa ter de 10 a 72 bytes.';
        }

        if ($erro !== null) {
            Flash::guardarCampos(['nome' => $nome, 'email' => $email]);

            return Response::redirect('/cadastro', 'danger', $erro);
        }

        try {
            (new Usuario(Db::pdo()))->cadastrarAluno($nome, $email, $senha);
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
