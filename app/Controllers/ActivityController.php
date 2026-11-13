<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use DomainException;
use ELabore\Models\Activity;
use ELabore\Services\SessionManager;
use InvalidArgumentException;
use OutOfBoundsException;

final class ActivityController
{
    public function __construct(private readonly SessionManager $sessions = new SessionManager())
    {
    }

    /** @return array{status:int, body:array<string, mixed>} */
    public function index(array $input = []): array
    {
        $user = $this->sessions->currentUser();
        if ($user === null) {
            return $this->error(401, 'Entre para consultar as atividades.');
        }

        $activities = (new Activity(\createDatabaseConnection()))->forUser($user['id'], $user['role']);

        return [
            'status' => 200,
            'body' => ['status' => 'ok', 'activities' => $activities],
        ];
    }

    /** @return array{status:int, body:array<string, mixed>} */
    public function create(array $input = []): array
    {
        $user = $this->sessions->currentUser();
        if ($user === null) {
            return $this->error(401, 'Entre para registrar uma atividade.');
        }
        if ($user['role'] !== 'aluno') {
            return $this->error(403, 'Somente alunos podem registrar atividades.');
        }

        try {
            $id = (new Activity(\createDatabaseConnection()))->create($user['id'], $input);
        } catch (InvalidArgumentException $exception) {
            return $this->error(422, $exception->getMessage());
        }

        return [
            'status' => 201,
            'body' => ['status' => 'ok', 'id' => $id, 'message' => 'Atividade registrada.'],
        ];
    }

    /** @return array{status:int, body:array<string, mixed>} */
    public function review(array $input = []): array
    {
        $user = $this->sessions->currentUser();
        if ($user === null) {
            return $this->error(401, 'Entre para validar uma atividade.');
        }
        if ($user['role'] !== 'professor') {
            return $this->error(403, 'Somente professores podem validar atividades.');
        }

        $activityId = filter_var($input['activity_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = $input['decision'] ?? null;
        $note = $input['note'] ?? null;
        if ($activityId === false || !is_string($decision) || ($note !== null && !is_string($note))) {
            return $this->error(422, 'Informe a atividade e uma decisão válida.');
        }

        try {
            (new Activity(\createDatabaseConnection()))->review(
                $activityId,
                $user['id'],
                $decision,
                $note
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error(422, $exception->getMessage());
        } catch (OutOfBoundsException $exception) {
            return $this->error(404, $exception->getMessage());
        } catch (DomainException $exception) {
            return $this->error(409, $exception->getMessage());
        }

        return [
            'status' => 200,
            'body' => ['status' => 'ok', 'message' => 'Atividade validada.'],
        ];
    }

    /** @return array{status:int, body:array<string, string>} */
    private function error(int $status, string $message): array
    {
        return [
            'status' => $status,
            'body' => ['status' => 'error', 'message' => $message],
        ];
    }
}
