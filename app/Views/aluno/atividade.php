<p class="small mb-2"><a href="/aluno/entregas" class="text-dark"><i class="bi bi-arrow-left"></i> Entregas</a></p>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
  <div>
    <p class="titulo-pagina mb-1">Entregas - <?= e($atividade['nome']) ?></p>
    <?php if (!empty($atividade['descricao'])): ?>
      <p class="small mb-0" style="white-space: pre-line; max-width: 760px;"><?= e($atividade['descricao']) ?></p>
    <?php endif; ?>
  </div>
  <?php if ($entregas !== []): ?>
    <a href="/aluno/atividades/<?= (int) $atividade['id'] ?>/entregas/nova" class="btn btn-el"><i class="bi bi-plus-lg me-1"></i>Nova entrega</a>
  <?php endif; ?>
</div>

<?php if ($entregas === []): ?>
  <div class="text-center py-5 my-3">
    <p class="fw-semibold mb-3">Você não possui entregas!</p>
    <a href="/aluno/atividades/<?= (int) $atividade['id'] ?>/entregas/nova" class="btn btn-el btn-sm">Nova entrega</a>
  </div>
<?php else: ?>
  <div class="d-grid gap-3" style="max-width: 820px;">
    <?php foreach ($entregas as $entrega): ?>
      <div class="item-entrega">
        <div class="cabecalho d-flex flex-wrap justify-content-between gap-2 px-3 py-2">
          <strong><?= e($entrega['titulo']) ?></strong>
          <span class="small">
            <i class="bi bi-calendar3"></i> <?= e(fmt_data($entrega['data'])) ?>
            · <i class="bi bi-clock"></i> <?= e(fmt_hora($entrega['hora_inicio'])) ?> – <?= e(fmt_hora($entrega['hora_fim'])) ?>
            (<?= e(fmt_duracao((int) $entrega['segundos'])) ?>)
          </span>
        </div>
        <div class="p-3">
          <?php if (!empty($entrega['descricao'])): ?>
            <p class="small" style="white-space: pre-line;"><?= e($entrega['descricao']) ?></p>
          <?php endif; ?>

          <?php if ($entrega['arquivos'] === []): ?>
            <p class="small text-secondary mb-0">Nenhum arquivo anexado nesta entrega.</p>
          <?php else: ?>
            <ul class="list-unstyled small mb-0">
              <?php foreach ($entrega['arquivos'] as $arquivo): ?>
                <li>
                  <i class="bi bi-paperclip"></i>
                  <a href="/arquivos/<?= (int) $arquivo['id'] ?>" target="_blank" rel="noopener"><?= e($arquivo['nome_arquivo']) ?></a>
                  <span class="text-secondary">(<?= e(fmt_tamanho((int) $arquivo['tamanho'])) ?>)</span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
