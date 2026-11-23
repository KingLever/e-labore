<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Support\Auth;
use ELabore\Support\Consultas;
use ELabore\Support\Db;
use ELabore\Support\Flash;
use ELabore\Support\Response;
use ELabore\Support\Uploads;
use DateTimeImmutable;
use Throwable;

final class AlunoController
{
    /** @return array<string, mixed> */
    public function home(): array
    {
        $aluno = Auth::exigir('aluno');
        $turmas = $this->turmas($aluno['id']);

        return Response::view('aluno/home', [
            'titulo' => 'Início',
            'turmas' => $turmas,
            'turma' => $this->turmaAtual($turmas),
        ]);
    }

    /** Lista as atividades da turma do aluno. */
    /** @return array<string, mixed> */
    public function entregas(): array
    {
        $aluno = Auth::exigir('aluno');
        $turmas = $this->turmas($aluno['id']);
        $turma = $this->turmaAtual($turmas);
        $atividades = [];

        if ($turma !== null) {
            $consulta = Db::pdo()->prepare(
                'SELECT a.id, a.nome, a.descricao, a.ativa,
                        (SELECT COUNT(*) FROM entregas e WHERE e.atividade_id = a.id AND e.aluno_id = ?) AS total_entregas
                 FROM atividades a
                 JOIN atividade_turmas att ON att.atividade_id = a.id
                 WHERE att.turma_id = ?
                 ORDER BY a.ativa DESC, a.criado_em DESC, a.id DESC'
            );
            $consulta->execute([$aluno['id'], $turma['id']]);
            $atividades = $consulta->fetchAll();
        }

        return Response::view('aluno/entregas', [
            'titulo' => 'Entregas',
            'turmas' => $turmas,
            'turma' => $turma,
            'atividades' => $atividades,
        ]);
    }

    /** Detalhes de uma atividade + tudo o que o aluno já enviou nela. */
    /** @return array<string, mixed> */
    public function atividade(int $id): array
    {
        $aluno = Auth::exigir('aluno');
        $atividade = $this->atividadeDoAluno($id, $aluno['id']);

        if ((int) $atividade['ativa'] !== 1) {
            return Response::redirect('/aluno/entregas', 'warning', 'Esta atividade está inativa.');
        }

        $consulta = Db::pdo()->prepare(
            'SELECT e.*, TIME_TO_SEC(TIMEDIFF(e.hora_fim, e.hora_inicio)) AS segundos
             FROM entregas e
             WHERE e.atividade_id = ? AND e.aluno_id = ?
             ORDER BY e.data DESC, e.id DESC'
        );
        $consulta->execute([$id, $aluno['id']]);

        return Response::view('aluno/atividade', [
            'titulo' => $atividade['nome'],
            'atividade' => $atividade,
            'entregas' => Consultas::entregasComArquivos($consulta->fetchAll()),
        ]);
    }

    /** @return array<string, mixed> */
    public function novaEntrega(int $id): array
    {
        $aluno = Auth::exigir('aluno');
        $atividade = $this->atividadeDoAluno($id, $aluno['id']);

        if ((int) $atividade['ativa'] !== 1) {
            return Response::redirect('/aluno/entregas', 'warning', 'Esta atividade está inativa.');
        }

        return Response::view('aluno/nova-entrega', [
            'titulo' => 'Enviar atividade',
            'atividade' => $atividade,
            'maxMb' => (int) (Uploads::MAX_BYTES / 1048576),
            'maxArquivos' => Uploads::MAX_ARQUIVOS,
        ]);
    }

    /** @return array<string, mixed> */
    public function storeEntrega(int $id): array
    {
        $aluno = Auth::exigir('aluno');
        $atividade = $this->atividadeDoAluno($id, $aluno['id']);
        $formulario = '/aluno/atividades/' . $id . '/entregas/nova';

        if ((int) $atividade['ativa'] !== 1) {
            return Response::redirect('/aluno/entregas', 'warning', 'Esta atividade está inativa.');
        }

        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $data = trim((string) ($_POST['data'] ?? ''));
        $inicio = trim((string) ($_POST['hora_inicio'] ?? ''));
        $fim = trim((string) ($_POST['hora_fim'] ?? ''));

        $erros = [];

        if ($titulo === '' || mb_strlen($titulo) > 255) {
            $erros[] = 'Informe o título da atividade realizada.';
        }

        if (mb_strlen($descricao) > 5000) {
            $erros[] = 'A descrição pode ter no máximo 5000 caracteres.';
        }

        $dataValida = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

        if (!$dataValida instanceof DateTimeImmutable || $dataValida->format('Y-m-d') !== $data) {
            $erros[] = 'Informe a data em que a atividade foi realizada.';
        } elseif ($dataValida > new DateTimeImmutable('today')) {
            $erros[] = 'A data não pode estar no futuro.';
        }

        $padraoHora = '/^([01]\d|2[0-3]):[0-5]\d$/';

        if (preg_match($padraoHora, $inicio) !== 1 || preg_match($padraoHora, $fim) !== 1) {
            $erros[] = 'Informe o horário inicial e o final.';
        } elseif ($fim <= $inicio) {
            $erros[] = 'O horário final precisa ser depois do inicial.';
        }

        $anexos = Uploads::validar('anexos');
        $erros = array_merge($erros, $anexos['erros']);

        if ($erros !== []) {
            Flash::guardarCampos($_POST);

            return Response::redirect($formulario, 'danger', implode(' ', $erros));
        }

        $pdo = Db::pdo();
        $guardados = [];
        $pdo->beginTransaction();

        try {
            $inserir = $pdo->prepare(
                'INSERT INTO entregas (atividade_id, aluno_id, titulo, descricao, data, hora_inicio, hora_fim)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $inserir->execute([$id, $aluno['id'], $titulo, $descricao === '' ? null : $descricao, $data, $inicio, $fim]);
            $entregaId = (int) $pdo->lastInsertId();

            $guardados = Uploads::guardar($anexos['arquivos']);
            $anexar = $pdo->prepare(
                'INSERT INTO comprovantes (entrega_id, nome_arquivo, caminho_arquivo, tipo, tamanho) VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($guardados as $arquivo) {
                $anexar->execute([$entregaId, $arquivo['nome'], $arquivo['armazenado'], $arquivo['mime'], $arquivo['tamanho']]);
            }

            $pdo->commit();
        } catch (Throwable $excecao) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            Uploads::apagar($guardados);
            error_log(sprintf('Falha ao salvar entrega: %s', $excecao->getMessage()));
            Flash::guardarCampos($_POST);

            return Response::redirect($formulario, 'danger', 'Não foi possível enviar. Tente novamente.');
        }

        return Response::redirect('/aluno/atividades/' . $id, 'success', 'Entrega enviada com sucesso!');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function turmas(int $alunoId): array
    {
        $consulta = Db::pdo()->prepare(
            'SELECT t.* FROM turma_alunos ta
             JOIN turmas t ON t.id = ta.turma_id
             WHERE ta.aluno_id = ?
             ORDER BY t.ano DESC, t.periodo DESC, t.curso'
        );
        $consulta->execute([$alunoId]);

        return $consulta->fetchAll();
    }

    /**
     * @param list<array<string, mixed>> $turmas
     * @return array<string, mixed>|null
     */
    private function turmaAtual(array $turmas): ?array
    {
        if ($turmas === []) {
            return null;
        }

        $pedida = (int) ($_GET['turma'] ?? 0);

        foreach ($turmas as $turma) {
            if ((int) $turma['id'] === $pedida) {
                return $turma;
            }
        }

        return $turmas[0];
    }

    /**
     * Só devolve a atividade se ela foi distribuída para alguma turma do aluno.
     *
     * @return array<string, mixed>
     */
    private function atividadeDoAluno(int $id, int $alunoId): array
    {
        $consulta = Db::pdo()->prepare(
            'SELECT a.* FROM atividades a
             JOIN atividade_turmas att ON att.atividade_id = a.id
             JOIN turma_alunos ta ON ta.turma_id = att.turma_id
             WHERE a.id = ? AND ta.aluno_id = ?
             LIMIT 1'
        );
        $consulta->execute([$id, $alunoId]);
        $atividade = $consulta->fetch();

        if (!is_array($atividade)) {
            Response::abort(404, 'Atividade não encontrada.');
        }

        return $atividade;
    }
}
