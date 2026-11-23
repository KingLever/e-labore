<?php if ($turma === null): ?>
  <p class="titulo-pagina">Início</p>
  <div class="alert alert-secondary" style="max-width: 640px;">
    Você ainda não está vinculado a nenhuma turma. Peça ao professor para vincular o seu e-mail à turma.
  </div>
<?php else: ?>
  <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <p class="titulo-pagina mb-0">Início - <?= e(turma_curta($turma)) ?></p>
    <?php if (count($turmas) > 1): ?>
      <div class="dropdown">
        <button class="btn btn-sm btn-el-outline dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Trocar de turma</button>
        <ul class="dropdown-menu">
          <?php foreach ($turmas as $opcao): ?>
            <li><a class="dropdown-item <?= (int) $opcao['id'] === (int) $turma['id'] ? 'active' : '' ?>" href="/aluno?turma=<?= (int) $opcao['id'] ?>"><?= e(turma_label($opcao)) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <div class="d-flex flex-wrap gap-3">
    <a class="card-menu" href="/aluno/entregas?turma=<?= (int) $turma['id'] ?>">
      <span class="icone"><i class="bi bi-book-fill"></i></span>
      <span class="rotulo">Entregas</span>
    </a>
  </div>
<?php endif; ?>
