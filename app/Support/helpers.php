<?php

declare(strict_types=1);

use ELabore\Support\Csrf;
use ELabore\Support\Flash;

function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function old(string $chave, mixed $padrao = ''): mixed
{
    return Flash::old($chave, $padrao);
}

function fmt_data(?string $data): string
{
    if ($data === null || $data === '') {
        return '';
    }

    $objeto = DateTimeImmutable::createFromFormat('Y-m-d', $data);

    return $objeto instanceof DateTimeImmutable ? $objeto->format('d/m/Y') : $data;
}

function fmt_hora(?string $hora): string
{
    return $hora === null ? '' : substr($hora, 0, 5);
}

function fmt_duracao(int $segundos): string
{
    $minutos = intdiv(max($segundos, 0), 60);

    return sprintf('%dh%02d', intdiv($minutos, 60), $minutos % 60);
}

function fmt_tamanho(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 0, ',', '.') . ' KB';
    }

    return $bytes . ' B';
}

function status_entrega(string $status): string
{
    $opcoes = [
        'pendente' => ['Pendente', 'warning'],
        'em_analise' => ['Em análise', 'secondary'],
        'aprovada' => ['Aprovada', 'success'],
        'recusada' => ['Recusada', 'danger'],
    ];
    [$rotulo, $cor] = $opcoes[$status] ?? ['Indefinido', 'secondary'];
    return '<span class="badge text-bg-' . $cor . '">' . e($rotulo) . '</span>';
}

function turma_curta(array $turma): string
{
    return $turma['curso'] . ' ' . $turma['ano'];
}

function turma_label(array $turma): string
{
    return turma_curta($turma) . ' · ' . $turma['periodo'] . 'º Período';
}
