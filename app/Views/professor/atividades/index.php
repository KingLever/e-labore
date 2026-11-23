<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <p class="titulo-pagina mb-0">Atividades</p>
  <a href="/professor/atividades/nova" class="btn btn-el"><i class="bi bi-plus-lg me-1"></i>Nova atividade</a>
</div>

<?php if ($atividades === []): ?>
  <div class="text-center py-5">
    <p class="fw-semibold mb-3">Você ainda não criou atividades.</p>
    <a href="/professor/atividades/nova" class="btn btn-el">Criar primeira atividade</a>
  </div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table-el">
      <thead>
        <tr><th>Atividade</th><th>Turmas</th><th>Situação</th><th>Entregas</th><th style="width: 330px;">Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($atividades as $atividade): ?>
          <?php $ativa = (int) $atividade['ativa'] === 1; $id = (int) $atividade['id']; ?>
          <tr>
            <td>
              <strong><?= e($atividade['nome']) ?></strong>
              <?php if (!empty($atividade['descricao'])): ?>
                <div class="small text-dark-emphasis"><?= e(mb_strimwidth((string) $atividade['descricao'], 0, 110, '…')) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php foreach ($turmasPorAtividade[$id] ?? [] as $turma): ?>
                <span class="badge text-bg-light border border-dark fw-normal"><?= e(turma_curta($turma)) ?></span>
              <?php endforeach; ?>
            </td>
            <td class="text-center">
              <span class="badge <?= $ativa ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $ativa ? 'Ativa' : 'Inativa' ?></span>
            </td>
            <td class="text-center"><a href="/professor/entregas/<?= $id ?>" class="text-dark"><?= (int) $atividade['total_entregas'] ?></a></td>
            <td class="text-center">
              <a class="btn btn-sm btn-el-outline" href="/professor/atividades/<?= $id ?>/editar"><i class="bi bi-pencil"></i> Editar</a>
              <form method="post" action="/professor/atividades/<?= $id ?>/status" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-el-outline">
                  <?= $ativa ? '<i class="bi bi-pause-circle"></i> Desativar' : '<i class="bi bi-play-circle"></i> Ativar' ?>
                </button>
              </form>
              <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalConfirmar"
                      data-acao="/professor/atividades/<?= $id ?>/excluir"
                      data-titulo="Excluir atividade"
                      data-texto="Você está prestes a excluir a atividade:"
                      data-nome="<?= e($atividade['nome']) ?>"
                      data-aviso="Serão apagados também todos os envios dos alunos nesta atividade (<?= (int) $atividade['total_entregas'] ?>) e os arquivos anexados. Essa ação não pode ser desfeita."
                      data-botao="Excluir atividade">
                <i class="bi bi-trash"></i> Excluir
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
