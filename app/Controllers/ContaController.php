<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Support\Auth;
use ELabore\Support\Db;
use ELabore\Support\Response;

/**
 * Contas (e-mails) cadastradas na plataforma e a quais turmas do professor cada uma está vinculada.
 */
final class ContaController
{
    /** @return array<string, mixed> */
    public function index(): array
    {
        $professor = Auth::exigir('professor');
        $busca = trim((string) ($_GET['q'] ?? ''));

        if ($busca !== '') {
            $consulta = Db::pdo()->prepare(
                "SELECT id, nome, email FROM usuarios
                 WHERE perfil = 'aluno' AND (nome LIKE ? OR email LIKE ?)
                 ORDER BY nome LIMIT 200"
            );
            $like = '%' . addcslashes($busca, '%_\\') . '%';
            $consulta->execute([$like, $like]);
        } else {
            $consulta = Db::pdo()->query("SELECT id, nome, email FROM usuarios WHERE perfil = 'aluno' ORDER BY nome LIMIT 200");
        }

        $contas = $consulta->fetchAll();
        $vinculos = [];

        if ($contas !== []) {
            $ids = array_map(static fn (array $c): int => (int) $c['id'], $contas);
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $busca_vinculos = Db::pdo()->prepare(
                "SELECT ta.aluno_id, t.id, t.curso, t.ano, t.periodo
                 FROM turma_alunos ta
                 JOIN turmas t ON t.id = ta.turma_id
                 WHERE t.professor_id = ? AND ta.aluno_id IN ($marcas)
                 ORDER BY t.ano DESC, t.periodo DESC"
            );
            $busca_vinculos->execute([$professor['id'], ...$ids]);

            foreach ($busca_vinculos->fetchAll() as $linha) {
                $vinculos[(int) $linha['aluno_id']][] = $linha;
            }
        }

        $turmas = Db::pdo()->prepare('SELECT * FROM turmas WHERE professor_id = ? ORDER BY ano DESC, periodo DESC, curso');
        $turmas->execute([$professor['id']]);

        return Response::view('professor/contas/index', [
            'titulo' => 'Contas',
            'contas' => $contas,
            'vinculos' => $vinculos,
            'turmas' => $turmas->fetchAll(),
            'busca' => $busca,
        ]);
    }

    /** @return array<string, mixed> */
    public function vincular(int $id): array
    {
        $professor = Auth::exigir('professor');
        $turmaId = (int) ($_POST['turma_id'] ?? 0);

        $turma = Db::pdo()->prepare('SELECT id FROM turmas WHERE id = ? AND professor_id = ?');
        $turma->execute([$turmaId, $professor['id']]);

        if ($turma->fetchColumn() === false) {
            return Response::voltar('/professor/contas', 'danger', 'Escolha uma das suas turmas.');
        }

        $conta = Db::pdo()->prepare("SELECT id FROM usuarios WHERE id = ? AND perfil = 'aluno'");
        $conta->execute([$id]);

        if ($conta->fetchColumn() === false) {
            return Response::voltar('/professor/contas', 'danger', 'Conta de aluno não encontrada.');
        }

        $inserir = Db::pdo()->prepare('INSERT IGNORE INTO turma_alunos (turma_id, aluno_id) VALUES (?, ?)');
        $inserir->execute([$turmaId, $id]);

        return Response::voltar('/professor/contas', 'success', 'Conta vinculada à turma.');
    }

    /** @return array<string, mixed> */
    public function desvincular(int $id): array
    {
        $professor = Auth::exigir('professor');
        $turmaId = (int) ($_POST['turma_id'] ?? 0);

        $apagar = Db::pdo()->prepare(
            'DELETE ta FROM turma_alunos ta
             JOIN turmas t ON t.id = ta.turma_id
             WHERE ta.aluno_id = ? AND ta.turma_id = ? AND t.professor_id = ?'
        );
        $apagar->execute([$id, $turmaId, $professor['id']]);

        return Response::voltar('/professor/contas', 'success', 'Vínculo com a turma removido.');
    }
}
