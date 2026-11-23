<?php

declare(strict_types=1);

namespace ELabore\Support;

final class Response
{
    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public static function view(string $view, array $dados = [], int $status = 200): array
    {
        return ['status' => $status, 'view' => $view, 'data' => $dados];
    }

    /**
     * @return array<string, mixed>
     */
    public static function redirect(string $destino, ?string $tipo = null, ?string $mensagem = null): array
    {
        if ($tipo !== null && $mensagem !== null) {
            Flash::add($tipo, $mensagem);
        }

        return ['status' => 303, 'redirect' => $destino];
    }

    /**
     * Volta para a página anterior (só dentro do próprio site).
     *
     * @return array<string, mixed>
     */
    public static function voltar(string $padrao, ?string $tipo = null, ?string $mensagem = null): array
    {
        $destino = $padrao;
        $referencia = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $partes = $referencia !== '' ? parse_url($referencia) : false;

        if (is_array($partes) && isset($partes['host'], $partes['path'])) {
            $host = $partes['host'] . (isset($partes['port']) ? ':' . $partes['port'] : '');

            if (
                $host === (string) ($_SERVER['HTTP_HOST'] ?? '')
                && str_starts_with($partes['path'], '/')
                && !str_starts_with($partes['path'], '//')
            ) {
                $destino = $partes['path'] . (isset($partes['query']) ? '?' . $partes['query'] : '');
            }
        }

        return self::redirect($destino, $tipo, $mensagem);
    }

    /**
     * @param array<string, mixed> $corpo
     * @return array<string, mixed>
     */
    public static function json(array $corpo, int $status = 200): array
    {
        return ['status' => $status, 'body' => $corpo];
    }

    /**
     * @return array<string, mixed>
     */
    public static function arquivo(string $caminho, string $nome, string $mime, bool $inline): array
    {
        return ['status' => 200, 'file' => ['path' => $caminho, 'name' => $nome, 'mime' => $mime, 'inline' => $inline]];
    }

    public static function abort(int $status, ?string $mensagem = null): never
    {
        $padroes = [403 => 'Você não tem permissão para acessar esta página.', 404 => 'Página não encontrada.'];

        throw new HttpException(
            self::view(
                'errors/erro',
                ['titulo' => 'Erro', 'codigo' => $status, 'mensagem' => $mensagem ?? ($padroes[$status] ?? 'Ocorreu um erro.')],
                $status
            )
        );
    }
}
