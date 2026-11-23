<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Support\Auth;
use ELabore\Support\Consultas;
use ELabore\Support\Db;
use ELabore\Support\Flash;
use ELabore\Support\Response;
use ELabore\Support\Uploads;
use Throwable;

final class AtividadeController
{
    /** @return array<string, mixed> */
    public function index(): array
    {
        $professor = Auth::exigir('professor');

        $consulta = Db::pdo()->prepare(
            'SELECT a.*, (SELECT COUNT(*) FROM entregas e WHERE e.atividade_id = a.id) AS total_entregas
             FROM atividades a
             WHERE a.professor_id = ?
             ORDER BY a.criado_em DESC, a.id DESC'
        );
        $consulta->execute([$professor['id']]);
        $atividades = $consulta->fetchAll();

        $ids = array_map(static fn (array $a): int => (int) $a['id'], $atividades);

        return Response::view('professor/atividades/index', [
            'titulo' => 'Atividades',
            'atividades' => $atividades,
            'turmasPorAtividade' => Consultas::turmasPorAtividade($ids),
        ]);
    }

    /** @return array<string, mixed> */
    public function create(): array
    {
        $professor = Auth::exigir('professor');

        return Response::view('professor/atividades/form', [
            'titulo' => 'Nova atividade',
            'atividade' => null,
            'turmas' => $this->turmasDoProfessor($professor['id']),
            'selecionadas' => [],
        ]);
    }

    /** @return array<string, mixed> */
    public function store(): array
    {
        $professor = Auth::exigir('professor');
        [$dados, $turmaIds, $erro] = $this->ler($professor['id']);

        if ($erro !== null) {
            Flash::guardarCampos($_POST);

            return Response::redirect('/professor/atividades/nova', 'danger', $erro);
        }

        $pdo = Db::pdo();
        $pdo->beginTransaction();

        try {
            $inserir = $pdo->prepare('INSERT INTO atividades (professor_id, nome, descricao, ativa) VALUES (?, ?, ?, ?)');
            $inserir->execute([$professor['id'], $dados['nome'], $dados['descricao'], $dados['ativa']]);
            $this->gravarTurmas((int) $pdo->lastInsertId(), $turmaIds);
            $pdo->commit();
        } catch (Throwable $excecao) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $excecao;
        }

        return Response::redirect('/professor/atividades', 'success', 'Atividade criada e distribuída para as turmas.');
    }

    /** @return array<string, mixed> */
    public function edit(int $id): array
    {
        $professor = Auth::exigir('professor');
        $atividade = $this->buscar($id, $professor['id']);

        $selecionadas = Db::pdo()->prepare('SELECT turma_id FROM atividade_turmas WHERE atividade_id = ?');
        $selecionadas->execute([$id]);

        return Response::view('professor/atividades/form', [
            'titulo' => 'Editar atividade',
            'atividade' => $atividade,
            'turmas' => $this->turmasDoProfessor($professor['id']),
            'selecionadas' => array_map('intval', array_column($selecionadas->fetchAll(), 'turma_id')),
        ]);
    }

    /** @return array<string, mixed> */
    public function update(int $id): array
    {
        $professor = Auth::exigir('professor');
        $this->buscar($id, $professor['id']);
        [$dados, $turmaIds, $erro] = $this->ler($professor['id']);

        if ($erro !== null) {
            Flash::guardarCampos($_POST);

            return Response::redirect('/professor/atividades/' . $id . '/editar', 'danger', $erro);
        }

        $pdo = Db::pdo();
        $pdo->beginTransaction();

        try {
            $atualizar = $pdo->prepare('UPDATE atividades SET nome = ?, descricao = ?, ativa = ? WHERE id = ? AND professor_id = ?');
            $atualizar->execute([$dados['nome'], $dados['descricao'], $dados['ativa'], $id, $professor['id']]);

            $limpar = $pdo->prepare('DELETE FROM atividade_turmas WHERE atividade_id = ?');
            $limpar->execute([$id]);
            $this->gravarTurmas($id, $turmaIds);

            $pdo->commit();
        } catch (Throwable $excecao) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $excecao;
        }

        return Response::redirect('/professor/atividades', 'success', 'Atividade atualizada.');
    }

    /** Ativa ou desativa a atividade. */
    /** @return array<string, mixed> */
    public function toggle(int $id): array
    {
        $professor = Auth::exigir('professor');
        $atividade = $this->buscar($id, $professor['id']);
        $nova = (int) $atividade['ativa'] === 1 ? 0 : 1;

        $atualizar = Db::pdo()->prepare('UPDATE atividades SET ativa = ? WHERE id = ? AND professor_id = ?');
        $atualizar->execute([$nova, $id, $professor['id']]);

        return Response::redirect(
            '/professor/atividades',
            'success',
            $nova === 1 ? 'Atividade ativada.' : 'Atividade desativada.'
        );
    }

    /** @return array<string, mixed> */
    public function destroy(int $id): array
    {
        $professor = Auth::exigir('professor');
        $atividade = $this->buscar($id, $professor['id']);

        $arquivos = Db::pdo()->prepare(
            'SELECT c.caminho_arquivo AS armazenado
             FROM comprovantes c
             JOIN entregas e ON e.id = c.entrega_id
             WHERE e.atividade_id = ?'
        );
        $arquivos->execute([$id]);
        $paraApagar = $arquivos->fetchAll();

        $apagar = Db::pdo()->prepare('DELETE FROM atividades WHERE id = ? AND professor_id = ?');
        $apagar->execute([$id, $professor['id']]);

        Uploads::apagar($paraApagar);

        return Response::redirect('/professor/atividades', 'success', 'Atividade "' . $atividade['nome'] . '" excluída.');
    }

    /**
     * @return array<string, mixed>
     */
    private function buscar(int $id, int $professorId): array
    {
        $consulta = Db::pdo()->prepare('SELECT * FROM atividades WHERE id = ? AND professor_id = ? LIMIT 1');
        $consulta->execute([$id, $professorId]);
        $atividade = $consulta->fetch();

        if (!is_array($atividade)) {
            Response::abort(404, 'Atividade não encontrada.');
        }

        return $atividade;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function turmasDoProfessor(int $professorId): array
    {
        $consulta = Db::pdo()->prepare('SELECT * FROM turmas WHERE professor_id = ? ORDER BY ano DESC, periodo DESC, curso');
        $consulta->execute([$professorId]);

        return $consulta->fetchAll();
    }

    /**
     * @return array{0: array{nome: string, descricao: string|null, ativa: int}, 1: list<int>, 2: string|null}
     */
    private function ler(int $professorId): array
    {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $ativa = isset($_POST['ativa']) ? 1 : 0;
        $pedidas = is_array($_POST['turmas'] ?? null) ? $_POST['turmas'] : [];
        $turmaIds = $this->turmasValidas($professorId, $pedidas);
        $erro = null;

        if ($nome === '' || mb_strlen($nome) > 255) {
            $erro = 'Informe o nome da atividade (até 255 caracteres).';
        } elseif (mb_strlen($descricao) > 5000) {
            $erro = 'A descrição pode ter no máximo 5000 caracteres.';
        } elseif ($turmaIds === []) {
            $erro = 'Selecione pelo menos uma turma.';
        }

        return [['nome' => $nome, 'descricao' => $descricao === '' ? null : $descricao, 'ativa' => $ativa], $turmaIds, $erro];
    }

    /**
     * Mantém só ids de turmas que pertencem ao professor.
     *
     * @param array<mixed> $pedidas
     * @return list<int>
     */
    private function turmasValidas(int $professorId, array $pedidas): array
    {
        $ids = [];

        foreach ($pedidas as $pedida) {
            if (is_scalar($pedida) && (int) $pedida > 0) {
                $ids[(int) $pedida] = (int) $pedida;
            }
        }

        if ($ids === []) {
            return [];
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $consulta = Db::pdo()->prepare("SELECT id FROM turmas WHERE professor_id = ? AND id IN ($marcas)");
        $consulta->execute([$professorId, ...array_values($ids)]);

        return array_map('intval', array_column($consulta->fetchAll(), 'id'));
    }

    /**
     * @param list<int> $turmaIds
     */
    private function gravarTurmas(int $atividadeId, array $turmaIds): void
    {
        $inserir = Db::pdo()->prepare('INSERT INTO atividade_turmas (atividade_id, turma_id) VALUES (?, ?)');

        foreach ($turmaIds as $turmaId) {
            $inserir->execute([$atividadeId, $turmaId]);
        }
    }
}
