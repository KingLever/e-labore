<?php

declare(strict_types=1);

namespace ELabore\Models;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use OutOfBoundsException;
use PDO;
use Throwable;

final class Activity
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return list<array<string, mixed>> */
    public function forUser(int $userId, string $role): array
    {
        if ($userId < 1 || !in_array($role, ['aluno', 'professor'], true)) {
            throw new InvalidArgumentException('Usuário ou perfil inválido.');
        }

        $condition = $role === 'aluno' ? 'a.aluno_id = :user_id' : "a.status = 'pendente'";
        $statement = $this->connection->prepare(
            'SELECT a.id, a.aluno_id AS student_id, u.nome AS student_name, '
            . 'a.titulo AS title, a.descricao AS description, a.`data` AS activity_date, '
            . 'a.hora_inicio AS start_time, a.hora_fim AS end_time, a.status, '
            . 'v.observacao AS review_note '
            . 'FROM atividades a '
            . 'INNER JOIN usuarios u ON u.id = a.aluno_id '
            . 'LEFT JOIN validacoes v ON v.atividade_id = a.id '
            . "WHERE {$condition} ORDER BY a.`data` DESC, a.hora_inicio DESC, a.id DESC"
        );
        $statement->execute($role === 'aluno' ? ['user_id' => $userId] : []);

        return array_map(static function (array $row): array {
            $start = substr((string) $row['start_time'], 0, 5);
            $end = substr((string) $row['end_time'], 0, 5);

            return [
                'id' => (int) $row['id'],
                'studentId' => (int) $row['student_id'],
                'studentName' => (string) $row['student_name'],
                'title' => (string) $row['title'],
                'description' => (string) ($row['description'] ?? ''),
                'date' => (string) $row['activity_date'],
                'start' => $start,
                'end' => $end,
                'hours' => self::durationHours($start, $end),
                'status' => (string) $row['status'],
                'reviewNote' => $row['review_note'] === null ? null : (string) $row['review_note'],
            ];
        }, $statement->fetchAll());
    }

    /** @param array{title?:mixed, description?:mixed, date?:mixed, start?:mixed, end?:mixed} $input */
    public function create(int $studentId, array $input): int
    {
        if ($studentId < 1) {
            throw new InvalidArgumentException('Aluno inválido.');
        }

        foreach (['title', 'description', 'date', 'start', 'end'] as $field) {
            if (!isset($input[$field]) || !is_string($input[$field])) {
                throw new InvalidArgumentException('Preencha todos os dados da atividade.');
            }
        }

        $title = trim($input['title']);
        $description = trim($input['description']);
        $date = trim($input['date']);
        $start = trim($input['start']);
        $end = trim($input['end']);
        $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
        $descriptionLength = function_exists('mb_strlen') ? mb_strlen($description, 'UTF-8') : strlen($description);

        if ($title === '' || $titleLength > 255) {
            throw new InvalidArgumentException('O título deve ter entre 1 e 255 caracteres.');
        }
        if ($description === '' || $descriptionLength > 5000) {
            throw new InvalidArgumentException('A descrição deve ter entre 1 e 5000 caracteres.');
        }
        if (!self::isDate($date)) {
            throw new InvalidArgumentException('Informe uma data válida.');
        }

        $startMinutes = self::timeInMinutes($start);
        $endMinutes = self::timeInMinutes($end);
        if ($startMinutes === null || $endMinutes === null || $endMinutes <= $startMinutes) {
            throw new InvalidArgumentException('O horário final deve ser posterior ao horário inicial.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO atividades (aluno_id, titulo, descricao, `data`, hora_inicio, hora_fim, status) '
            . "VALUES (:student_id, :title, :description, :activity_date, :start_time, :end_time, 'pendente')"
        );
        $statement->execute([
            'student_id' => $studentId,
            'title' => $title,
            'description' => $description,
            'activity_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function review(int $activityId, int $professorId, string $decision, ?string $note): void
    {
        if ($activityId < 1 || $professorId < 1 || !in_array($decision, ['aprovada', 'recusada'], true)) {
            throw new InvalidArgumentException('Dados de validação inválidos.');
        }

        $note = $note === null ? null : trim($note);
        if ($decision === 'recusada' && ($note === null || $note === '')) {
            throw new InvalidArgumentException('Informe o motivo da recusa.');
        }
        if ($note !== null && strlen($note) > 5000) {
            throw new InvalidArgumentException('A observação deve ter até 5000 bytes.');
        }
        if ($note === '') {
            $note = null;
        }

        $this->connection->beginTransaction();
        try {
            $lookup = $this->connection->prepare('SELECT status FROM atividades WHERE id = :id FOR UPDATE');
            $lookup->execute(['id' => $activityId]);
            $status = $lookup->fetchColumn();

            if ($status === false) {
                throw new OutOfBoundsException('Atividade não encontrada.');
            }
            if ($status !== 'pendente') {
                throw new DomainException('Esta atividade já foi validada.');
            }

            $update = $this->connection->prepare('UPDATE atividades SET status = :status WHERE id = :id');
            $update->execute(['status' => $decision, 'id' => $activityId]);

            $validation = $this->connection->prepare(
                'INSERT INTO validacoes (atividade_id, professor_id, decisao, observacao) '
                . 'VALUES (:activity_id, :professor_id, :decision, :note)'
            );
            $validation->execute([
                'activity_id' => $activityId,
                'professor_id' => $professorId,
                'decision' => $decision,
                'note' => $note,
            ]);

            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    private static function isDate(string $date): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts)) {
            return false;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    private static function timeInMinutes(string $time): ?int
    {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $parts)) {
            return null;
        }

        return ((int) $parts[1] * 60) + (int) $parts[2];
    }

    private static function durationHours(string $start, string $end): float
    {
        $startTime = DateTimeImmutable::createFromFormat('!H:i', $start);
        $endTime = DateTimeImmutable::createFromFormat('!H:i', $end);
        if ($startTime === false || $endTime === false) {
            return 0.0;
        }

        return round(($endTime->getTimestamp() - $startTime->getTimestamp()) / 3600, 2);
    }
}
