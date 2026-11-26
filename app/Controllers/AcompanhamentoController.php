<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Support\Auth;
use ELabore\Support\Consultas;
use ELabore\Support\Db;
use ELabore\Support\Response;
use ELabore\Models\Entrega;

/**
 * Tela "Entregas" do professor: acompanha o que os alunos enviaram em cada atividade.
 */
final class AcompanhamentoController
{
    public function validar(int $id, int $entrega): array
    {
        $professor = Auth::exigir('professor');
        $decisao = $_POST['decisao'] ?? '';
        $observacao = $_POST['observacao'] ?? '';
        $destino = '/professor/entregas/' . $id;
        if (!is_string($decisao) || !is_string($observacao)) {
            return Response::redirect($destino, 'danger', 'Dados inválidos.');
        }
        try {
            (new Entrega(Db::pdo()))->validar($id, $entrega, $professor['id'], $decisao, $observacao);
        } catch (\OutOfBoundsException) {
            Response::abort(404, 'Entrega não encontrada.');
        } catch (\InvalidArgumentException | \DomainException $exception) {
            return Response::redirect($destino, 'danger', $exception->getMessage());
        }
        return Response::redirect($destino, 'success', 'Entrega validada com sucesso.');
    }
    /** @return array<string, mixed> */
    public function index(): array
    {
        $professor = Auth::exigir('professor');

        $consulta = Db::pdo()->prepare(
            'SELECT a.id, a.nome, a.ativa, (SELECT COUNT(*) FROM entregas e WHERE e.atividade_id = a.id) AS total_entregas
             FROM atividades a
             WHERE a.professor_id = ?
             ORDER BY a.ativa DESC, a.criado_em DESC'
        );
        $consulta->execute([$professor['id']]);
        $atividades = $consulta->fetchAll();
        $ids = array_map(static fn (array $a): int => (int) $a['id'], $atividades);

        return Response::view('professor/entregas/index', [
            'titulo' => 'Entregas',
            'atividades' => $atividades,
            'turmasPorAtividade' => Consultas::turmasPorAtividade($ids),
        ]);
    }

    /** @return array<string, mixed> */
    public function show(int $id): array
    {
        $professor = Auth::exigir('professor');

        $consulta = Db::pdo()->prepare('SELECT * FROM atividades WHERE id = ? AND professor_id = ? LIMIT 1');
        $consulta->execute([$id, $professor['id']]);
        $atividade = $consulta->fetch();

        if (!is_array($atividade)) {
            Response::abort(404, 'Atividade não encontrada.');
        }

        $alunos = Db::pdo()->prepare(
            'SELECT DISTINCT u.id, u.nome, u.email
             FROM atividade_turmas att
             JOIN turma_alunos ta ON ta.turma_id = att.turma_id
             JOIN usuarios u ON u.id = ta.aluno_id
             WHERE att.atividade_id = ?
             ORDER BY u.nome'
        );
        $alunos->execute([$id]);

        $entregas = Db::pdo()->prepare(
            'SELECT e.*, u.nome AS aluno_nome, TIME_TO_SEC(TIMEDIFF(e.hora_fim, e.hora_inicio)) AS segundos,
                    (SELECT v.observacao FROM validacoes v WHERE v.entrega_id = e.id ORDER BY v.id DESC LIMIT 1) AS observacao_validacao
             FROM entregas e
             JOIN usuarios u ON u.id = e.aluno_id
             WHERE e.atividade_id = ?
             ORDER BY e.data DESC, e.id DESC'
        );
        $entregas->execute([$id]);
        $entregas = Consultas::entregasComArquivos($entregas->fetchAll());

        $resumo = [];

        foreach ($alunos->fetchAll() as $aluno) {
            $resumo[(int) $aluno['id']] = $aluno + ['total_entregas' => 0, 'segundos' => 0];
        }

        foreach ($entregas as $entrega) {
            $alunoId = (int) $entrega['aluno_id'];

            if (isset($resumo[$alunoId])) {
                $resumo[$alunoId]['total_entregas']++;
                if ($entrega['status'] === 'aprovada') {
                    $resumo[$alunoId]['segundos'] += (int) $entrega['segundos'];
                }
            }
        }

        return Response::view('professor/entregas/show', [
            'titulo' => $atividade['nome'],
            'atividade' => $atividade,
            'turmas' => Consultas::turmasPorAtividade([$id])[$id] ?? [],
            'resumo' => array_values($resumo),
            'entregas' => $entregas,
        ]);
    }
}
