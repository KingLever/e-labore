<?php

declare(strict_types=1);

namespace ELabore\Models;

use DomainException;
use InvalidArgumentException;
use OutOfBoundsException;
use PDO;
use Throwable;

final class Entrega
{
    public function __construct(private readonly PDO $pdo) {}

    public function validar(int $atividade, int $entrega, int $professor, string $decisao, string $observacao): void
    {
        $observacao = trim($observacao);
        if (!in_array($decisao, ['aprovada', 'recusada'], true) || ($decisao === 'recusada' && $observacao === '') || mb_strlen($observacao) > 1000) {
            throw new InvalidArgumentException('Informe uma decisão válida e o motivo da recusa (até 1000 caracteres).');
        }

        $this->pdo->beginTransaction();
        try {
            $consulta = $this->pdo->prepare('SELECT e.status FROM entregas e JOIN atividades a ON a.id = e.atividade_id WHERE e.id = ? AND a.id = ? AND a.professor_id = ? FOR UPDATE');
            $consulta->execute([$entrega, $atividade, $professor]);
            $status = $consulta->fetchColumn();
            if ($status === false) throw new OutOfBoundsException('Entrega não encontrada.');
            if (!in_array($status, ['pendente', 'em_analise'], true)) throw new DomainException('Esta entrega já foi validada.');

            $this->pdo->prepare('UPDATE entregas SET status = ? WHERE id = ?')->execute([$decisao, $entrega]);
            $this->pdo->prepare('INSERT INTO validacoes (entrega_id, professor_id, decisao, observacao) VALUES (?, ?, ?, ?)')->execute([$entrega, $professor, $decisao, $observacao === '' ? null : $observacao]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function horasAprovadas(int $aluno, int $atividade): int
    {
        $consulta = $this->pdo->prepare("SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(hora_fim, hora_inicio))), 0) FROM entregas WHERE aluno_id = ? AND atividade_id = ? AND status = 'aprovada'");
        $consulta->execute([$aluno, $atividade]);
        return (int) $consulta->fetchColumn();
    }
}
