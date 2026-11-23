<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Support\Auth;
use ELabore\Support\Db;
use ELabore\Support\Flash;
use ELabore\Support\Response;
use PDOException;

final class TurmaController
{
    /** @return array<string, mixed> */
    public function index(): array
    {
        $professor = Auth::exigir('professor');

        $consulta = Db::pdo()->prepare(
            'SELECT t.*, (SELECT COUNT(*) FROM turma_alunos ta WHERE ta.turma_id = t.id) AS total_alunos
             FROM turmas t
             WHERE t.professor_id = ?
             ORDER BY t.ano DESC, t.periodo DESC, t.curso'
        );
        $consulta->execute([$professor['id']]);

        return Response::view('professor/turmas/index', ['titulo' => 'Turmas', 'turmas' => $consulta->fetchAll()]);
    }

    /** @return array<string, mixed> */
    public function store(): array
    {
        $professor = Auth::exigir('professor');
        [$dados, $erro] = $this->lerTurma();

        if ($erro !== null) {
            Flash::guardarCampos($_POST);

            return Response::redirect('/professor/turmas', 'danger', $erro);
        }

        try {
            $inserir = Db::pdo()->prepare('INSERT INTO turmas (professor_id, curso, ano, periodo) VALUES (?, ?, ?, ?)');
            $inserir->execute([$professor['id'], $dados['curso'], $dados['ano'], $dados['periodo']]);
        } catch (PDOException $excecao) {
            if ($excecao->getCode() === '23000') {
                Flash::guardarCampos($_POST);

                return Response::redirect('/professor/turmas', 'danger', 'Você já tem uma turma com esse curso, ano e período.');
            }

            throw $excecao;
        }

        return Response::redirect('/professor/turmas', 'success', 'Turma criada com sucesso.');
    }

    /** @return array<string, mixed> */
    public function show(int $id): array
    {
        $professor = Auth::exigir('professor');
        $turma = $this->buscar($id, $professor['id']);

        $alunos = Db::pdo()->prepare(
            'SELECT u.id, u.nome, u.email
             FROM turma_alunos ta
             JOIN usuarios u ON u.id = ta.aluno_id
             WHERE ta.turma_id = ?
             ORDER BY u.nome'
        );
        $alunos->execute([$id]);

        $sugestoes = Db::pdo()->prepare(
            "SELECT email FROM usuarios
             WHERE perfil = 'aluno' AND id NOT IN (SELECT aluno_id FROM turma_alunos WHERE turma_id = ?)
             ORDER BY email
             LIMIT 500"
        );
        $sugestoes->execute([$id]);

        return Response::view('professor/turmas/show', [
            'titulo' => turma_curta($turma),
            'turma' => $turma,
            'alunos' => $alunos->fetchAll(),
            'sugestoes' => array_column($sugestoes->fetchAll(), 'email'),
        ]);
    }

    /** @return array<string, mixed> */
    public function update(int $id): array
    {
        $professor = Auth::exigir('professor');
        $this->buscar($id, $professor['id']);
        [$dados, $erro] = $this->lerTurma();

        if ($erro !== null) {
            return Response::redirect('/professor/turmas/' . $id, 'danger', $erro);
        }

        try {
            $atualizar = Db::pdo()->prepare('UPDATE turmas SET curso = ?, ano = ?, periodo = ? WHERE id = ? AND professor_id = ?');
            $atualizar->execute([$dados['curso'], $dados['ano'], $dados['periodo'], $id, $professor['id']]);
        } catch (PDOException $excecao) {
            if ($excecao->getCode() === '23000') {
                return Response::redirect('/professor/turmas/' . $id, 'danger', 'Você já tem uma turma com esse curso, ano e período.');
            }

            throw $excecao;
        }

        return Response::redirect('/professor/turmas/' . $id, 'success', 'Turma atualizada.');
    }

    /** @return array<string, mixed> */
    public function destroy(int $id): array
    {
        $professor = Auth::exigir('professor');
        $turma = $this->buscar($id, $professor['id']);

        $apagar = Db::pdo()->prepare('DELETE FROM turmas WHERE id = ? AND professor_id = ?');
        $apagar->execute([$id, $professor['id']]);

        return Response::redirect('/professor/turmas', 'success', 'Turma ' . turma_curta($turma) . ' excluída.');
    }

    /** Vincula uma conta de aluno (pelo e-mail) à turma. */
    /** @return array<string, mixed> */
    public function addAluno(int $id): array
    {
        $professor = Auth::exigir('professor');
        $this->buscar($id, $professor['id']);
        $destino = '/professor/turmas/' . $id;

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($email === '') {
            return Response::redirect($destino, 'danger', 'Informe o e-mail do aluno.');
        }

        $consulta = Db::pdo()->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND perfil = 'aluno' LIMIT 1");
        $consulta->execute([$email]);
        $aluno = $consulta->fetch();

        if (!is_array($aluno)) {
            return Response::redirect(
                $destino,
                'danger',
                'Não existe conta de aluno com esse e-mail. Peça para o aluno se cadastrar primeiro.'
            );
        }

        $inserir = Db::pdo()->prepare('INSERT IGNORE INTO turma_alunos (turma_id, aluno_id) VALUES (?, ?)');
        $inserir->execute([$id, $aluno['id']]);

        if ($inserir->rowCount() === 0) {
            return Response::redirect($destino, 'info', $aluno['nome'] . ' já está nesta turma.');
        }

        return Response::redirect($destino, 'success', $aluno['nome'] . ' foi adicionado à turma.');
    }

    /** @return array<string, mixed> */
    public function updateAluno(int $id, int $alunoId): array
    {
        $professor = Auth::exigir('professor');
        $this->buscar($id, $professor['id']);
        $destino = '/professor/turmas/' . $id;

        $vinculo = Db::pdo()->prepare('SELECT 1 FROM turma_alunos WHERE turma_id = ? AND aluno_id = ?');
        $vinculo->execute([$id, $alunoId]);

        if ($vinculo->fetchColumn() === false) {
            return Response::redirect($destino, 'danger', 'Aluno não encontrado nesta turma.');
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($nome === '' || mb_strlen($nome) > 255) {
            return Response::redirect($destino, 'danger', 'Informe o nome do aluno.');
        }

        if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::redirect($destino, 'danger', 'Informe um e-mail válido.');
        }

        try {
            $atualizar = Db::pdo()->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ? AND perfil = 'aluno'");
            $atualizar->execute([$nome, $email, $alunoId]);
        } catch (PDOException $excecao) {
            if ($excecao->getCode() === '23000') {
                return Response::redirect($destino, 'danger', 'Já existe outra conta com esse e-mail.');
            }

            throw $excecao;
        }

        return Response::redirect($destino, 'success', 'Dados do aluno atualizados.');
    }

    /** Remove o aluno da turma (a conta continua existindo). */
    /** @return array<string, mixed> */
    public function removeAluno(int $id, int $alunoId): array
    {
        $professor = Auth::exigir('professor');
        $this->buscar($id, $professor['id']);

        $apagar = Db::pdo()->prepare('DELETE FROM turma_alunos WHERE turma_id = ? AND aluno_id = ?');
        $apagar->execute([$id, $alunoId]);

        return Response::redirect('/professor/turmas/' . $id, 'success', 'Aluno removido da turma.');
    }

    /**
     * @return array<string, mixed>
     */
    private function buscar(int $id, int $professorId): array
    {
        $consulta = Db::pdo()->prepare('SELECT * FROM turmas WHERE id = ? AND professor_id = ? LIMIT 1');
        $consulta->execute([$id, $professorId]);
        $turma = $consulta->fetch();

        if (!is_array($turma)) {
            Response::abort(404, 'Turma não encontrada.');
        }

        return $turma;
    }

    /**
     * @return array{0: array{curso: string, ano: string, periodo: int}, 1: string|null}
     */
    private function lerTurma(): array
    {
        $curso = trim((string) ($_POST['curso'] ?? ''));
        $ano = trim((string) ($_POST['ano'] ?? ''));
        $periodo = (int) ($_POST['periodo'] ?? 0);
        $erro = null;

        if ($curso === '' || mb_strlen($curso) > 100) {
            $erro = 'Informe o curso (até 100 caracteres).';
        } elseif (preg_match('#^\d{4}/[12]$#', $ano) !== 1) {
            $erro = 'Informe o ano no formato 2026/1.';
        } elseif ($periodo < 1 || $periodo > 10) {
            $erro = 'Escolha o período.';
        }

        return [['curso' => $curso, 'ano' => $ano, 'periodo' => $periodo], $erro];
    }
}
