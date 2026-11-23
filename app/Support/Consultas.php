<?php

declare(strict_types=1);

namespace ELabore\Support;

/**
 * Consultas ao banco usadas por mais de uma tela.
 */
final class Consultas
{
    /**
     * @param list<int> $atividadeIds
     * @return array<int, list<array<string, mixed>>> turmas agrupadas por atividade
     */
    public static function turmasPorAtividade(array $atividadeIds): array
    {
        if ($atividadeIds === []) {
            return [];
        }

        $marcas = implode(',', array_fill(0, count($atividadeIds), '?'));
        $consulta = Db::pdo()->prepare(
            "SELECT att.atividade_id, t.id, t.curso, t.ano, t.periodo
             FROM atividade_turmas att
             JOIN turmas t ON t.id = att.turma_id
             WHERE att.atividade_id IN ($marcas)
             ORDER BY t.ano DESC, t.periodo DESC, t.curso"
        );
        $consulta->execute(array_values($atividadeIds));

        $mapa = [];

        foreach ($consulta->fetchAll() as $linha) {
            $mapa[(int) $linha['atividade_id']][] = $linha;
        }

        return $mapa;
    }

    /**
     * Acrescenta a cada entrega a lista de arquivos anexados.
     *
     * @param list<array<string, mixed>> $entregas
     * @return list<array<string, mixed>>
     */
    public static function entregasComArquivos(array $entregas): array
    {
        if ($entregas === []) {
            return [];
        }

        $ids = array_map(static fn (array $entrega): int => (int) $entrega['id'], $entregas);
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $consulta = Db::pdo()->prepare(
            "SELECT id, entrega_id, nome_arquivo, tipo, tamanho FROM comprovantes WHERE entrega_id IN ($marcas) ORDER BY id"
        );
        $consulta->execute($ids);

        $mapa = [];

        foreach ($consulta->fetchAll() as $arquivo) {
            $mapa[(int) $arquivo['entrega_id']][] = $arquivo;
        }

        foreach ($entregas as $indice => $entrega) {
            $entregas[$indice]['arquivos'] = $mapa[(int) $entrega['id']] ?? [];
        }

        return $entregas;
    }
}
