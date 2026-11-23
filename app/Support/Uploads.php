<?php

declare(strict_types=1);

namespace ELabore\Support;

use RuntimeException;

final class Uploads
{
    public const MAX_BYTES = 25 * 1024 * 1024;
    public const MAX_ARQUIVOS = 10;

    /** Formatos aceitos e o tipo (MIME) que cada um usa. */
    private const TIPOS = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /** Formatos cujo conteúdo real é conferido, não só a extensão. */
    private const CONFERIR = ['pdf', 'jpg', 'jpeg', 'png'];

    public static function pasta(): string
    {
        return dirname(__DIR__, 2) . '/storage/uploads';
    }

    public static function caminho(string $armazenado): string
    {
        return self::pasta() . '/' . basename($armazenado);
    }

    public static function podeExibirNoNavegador(string $mime): bool
    {
        return in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true);
    }

    /**
     * @return array{arquivos: list<array{tmp: string, nome: string, ext: string, tamanho: int, mime: string}>, erros: list<string>}
     */
    public static function validar(string $campo): array
    {
        $arquivos = [];
        $erros = [];
        $enviados = $_FILES[$campo] ?? null;

        if (!is_array($enviados) || !is_array($enviados['name'] ?? null)) {
            return ['arquivos' => [], 'erros' => []];
        }

        foreach (array_keys($enviados['name']) as $i) {
            $nome = basename((string) $enviados['name'][$i]);
            $codigo = (int) $enviados['error'][$i];

            if ($codigo === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($codigo === UPLOAD_ERR_INI_SIZE || $codigo === UPLOAD_ERR_FORM_SIZE) {
                $erros[] = sprintf('"%s" passa do limite de tamanho do servidor.', $nome);
                continue;
            }

            if ($codigo !== UPLOAD_ERR_OK) {
                $erros[] = sprintf('Não foi possível receber "%s". Tente novamente.', $nome);
                continue;
            }

            $tmp = (string) $enviados['tmp_name'][$i];
            $tamanho = (int) $enviados['size'][$i];
            $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

            if (!is_uploaded_file($tmp)) {
                $erros[] = sprintf('Não foi possível receber "%s".', $nome);
            } elseif ($tamanho > self::MAX_BYTES) {
                $erros[] = sprintf('"%s" passa do limite de 25 MB.', $nome);
            } elseif (!isset(self::TIPOS[$ext])) {
                $erros[] = sprintf('"%s": formato não permitido (use PDF, JPG, PNG, DOC ou DOCX).', $nome);
            } elseif (in_array($ext, self::CONFERIR, true) && !self::conteudoConfere($tmp, self::TIPOS[$ext])) {
                $erros[] = sprintf('"%s" não parece ser um arquivo %s válido.', $nome, strtoupper($ext));
            } else {
                $arquivos[] = ['tmp' => $tmp, 'nome' => $nome, 'ext' => $ext, 'tamanho' => $tamanho, 'mime' => self::TIPOS[$ext]];
            }
        }

        if (count($arquivos) > self::MAX_ARQUIVOS) {
            $erros[] = sprintf('Envie no máximo %d arquivos por vez.', self::MAX_ARQUIVOS);
        }

        return ['arquivos' => $arquivos, 'erros' => $erros];
    }

    /**
     * Move os arquivos validados para storage/uploads. Se algum falhar, apaga os já movidos.
     *
     * @param list<array{tmp: string, nome: string, ext: string, tamanho: int, mime: string}> $arquivos
     * @return list<array{armazenado: string, nome: string, mime: string, tamanho: int}>
     */
    public static function guardar(array $arquivos): array
    {
        $pasta = self::pasta();

        if (!is_dir($pasta) && !mkdir($pasta, 0775, true) && !is_dir($pasta)) {
            throw new RuntimeException('Não foi possível criar a pasta de uploads.');
        }

        $guardados = [];

        foreach ($arquivos as $arquivo) {
            $armazenado = bin2hex(random_bytes(16)) . '.' . $arquivo['ext'];

            if (!move_uploaded_file($arquivo['tmp'], $pasta . '/' . $armazenado)) {
                self::apagar($guardados);

                throw new RuntimeException('Falha ao gravar o arquivo enviado.');
            }

            $guardados[] = [
                'armazenado' => $armazenado,
                'nome' => mb_substr($arquivo['nome'], 0, 255),
                'mime' => $arquivo['mime'],
                'tamanho' => $arquivo['tamanho'],
            ];
        }

        return $guardados;
    }

    /**
     * @param list<array{armazenado: string}> $guardados
     */
    public static function apagar(array $guardados): void
    {
        foreach ($guardados as $arquivo) {
            @unlink(self::caminho($arquivo['armazenado']));
        }
    }

    private static function conteudoConfere(string $tmp, string $esperado): bool
    {
        if (!class_exists('finfo')) {
            return true;
        }

        $detectado = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);

        return $detectado === $esperado;
    }
}
