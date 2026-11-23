<p class="small mb-2"><a href="/aluno<?= $turma !== null ? '?turma=' . (int) $turma['id'] : '' ?>" class="text-dark"><i class="bi bi-arrow-left"></i> Início</a></p>

<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
  <p class="titulo-pagina mb-0">Entregas<?= $turma !== null ? ' - ' . e(turma_curta($turma)) : '' ?></p>
  <?php if (count($turmas) > 1): ?>
    <div class="dropdown">
      <button class="btn btn-sm btn-el-outline dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Trocar de turma</button>
      <ul class="dropdown-menu">
        <?php foreach ($turmas as $opcao): ?>
          <li><a class="dropdown-item <?= $turma !== null && (int) $opcao['id'] === (int) $turma['id'] ? 'active' : '' ?>" href="/aluno/entregas?turma=<?= (int) $opcao['id'] ?>"><?= e(turma_label($opcao)) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>

<?php if ($turma === null): ?>
  <div class="alert alert-secondary" style="max-width: 640px;">Você ainda não está vinculado a nenhuma turma. Peça ao professor para vincular o seu e-mail.</div>
<?php elseif ($atividades === []): ?>
  <p class="text-center fw-semibold py-5">Nenhuma atividade foi disponibilizada para a sua turma ainda.</p>
<?php else: ?>
  <div class="d-grid gap-3" style="max-width: 820px;">
    <?php foreach ($atividades as $atividade): ?>
      <?php
      $ativa = (int) $atividade['ativa'] === 1;
      $total = (int) $atividade['total_entregas'];
      $tag = $ativa ? 'a' : 'div';
      $href = $ativa ? ' href="/aluno/atividades/' . (int) $atividade['id'] . '"' : '';
      ?>
      <<?= $tag ?> class="item-atividade p-3 <?= $ativa ? '' : 'inativa' ?>"<?= $href ?>>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
          <div>
            <p class="fw-semibold mb-1"><?= e($atividade['nome']) ?></p>
            <?php if (!empty($atividade['descricao'])): ?>
              <p class="small mb-0"><?= e(mb_strimwidth((string) $atividade['descricao'], 0, 160, '…')) ?></p>
            <?php endif; ?>
          </div>
          <div class="text-end">
            <span class="badge <?= $ativa ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $ativa ? 'Ativa' : 'Inativa' ?></span>
            <?php if ($total > 0): ?>
              <span class="badge text-bg-primary"><i class="bi bi-check2"></i> <?= $total ?> envio<?= $total > 1 ? 's' : '' ?></span>
            <?php else: ?>
              <span class="badge text-bg-warning">Sem envios</span>
            <?php endif; ?>
          </div>
        </div>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
