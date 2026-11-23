<?php

declare(strict_types=1);

namespace ELabore\Controllers;

use ELabore\Support\Db;
use ELabore\Support\Auth;
use ELabore\Support\HttpException;
use ELabore\Support\Response;
use ELabore\Support\Uploads;

final class ArquivoController
{
    /** Entrega o arquivo só para o aluno que enviou ou para o professor da atividade. */
    /** @return array<string, mixed> */
    public function download(int $id): array
    {
        $usuario = Auth::usuario();

        if ($usuario === null) {
            throw new HttpException(Response::redirect('/login', 'warning', 'Entre para continuar.'));
        }

        $consulta = Db::pdo()->prepare(
            'SELECT c.nome_arquivo, c.caminho_arquivo, c.tipo, e.aluno_id, a.professor_id
             FROM comprovantes c
             JOIN entregas e ON e.id = c.entrega_id
             JOIN atividades a ON a.id = e.atividade_id
             WHERE c.id = ?
             LIMIT 1'
        );
        $consulta->execute([$id]);
        $arquivo = $consulta->fetch();

        $permitido = is_array($arquivo) && (
            ($usuario['perfil'] === 'aluno' && (int) $arquivo['aluno_id'] === $usuario['id'])
            || ($usuario['perfil'] === 'professor' && (int) $arquivo['professor_id'] === $usuario['id'])
        );

        if (!$permitido) {
            Response::abort(404, 'Arquivo não encontrado.');
        }

        $caminho = Uploads::caminho((string) $arquivo['caminho_arquivo']);

        if (!is_file($caminho)) {
            Response::abort(404, 'O arquivo não está mais disponível no servidor.');
        }

        $mime = (string) ($arquivo['tipo'] ?? 'application/octet-stream');

        return Response::arquivo($caminho, (string) $arquivo['nome_arquivo'], $mime, Uploads::podeExibirNoNavegador($mime));
    }
}
