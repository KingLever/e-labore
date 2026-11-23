<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <p class="titulo-pagina mb-0">Turmas</p>
  <button type="button" class="btn btn-el" data-bs-toggle="modal" data-bs-target="#modalNovaTurma">
    <i class="bi bi-plus-lg me-1"></i>Nova turma
  </button>
</div>

<?php if ($turmas === []): ?>
  <div class="text-center py-5">
    <p class="fw-semibold mb-3">Você ainda não possui turmas.</p>
    <button type="button" class="btn btn-el" data-bs-toggle="modal" data-bs-target="#modalNovaTurma">Criar primeira turma</button>
  </div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table-el">
      <thead>
        <tr>
          <th>Curso</th>
          <th>Ano</th>
          <th>Período</th>
          <th>Alunos</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($turmas as $turma): ?>
          <tr>
            <td class="text-center"><a class="fw-semibold" href="/professor/turmas/<?= (int) $turma['id'] ?>"><?= e($turma['curso']) ?></a></td>
            <td class="text-center"><?= e($turma['ano']) ?></td>
            <td class="text-center"><?= (int) $turma['periodo'] ?>º Período</td>
            <td class="text-center"><?= (int) $turma['total_alunos'] ?></td>
            <td class="text-center">
              <a class="btn btn-sm btn-el-outline" href="/professor/turmas/<?= (int) $turma['id'] ?>">Gerenciar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<div class="modal fade" id="modalNovaTurma" tabindex="-1" aria-labelledby="tituloNovaTurma" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="/professor/turmas" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="tituloNovaTurma">Nova turma</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="novaCurso" class="form-label">Curso</label>
          <input type="text" class="form-control" id="novaCurso" name="curso" value="<?= e(old('curso', 'ADS')) ?>" maxlength="100" required>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label for="novaAno" class="form-label">Ano</label>
            <input type="text" class="form-control" id="novaAno" name="ano" value="<?= e(old('ano')) ?>" placeholder="2026/1" pattern="\d{4}/[12]" required>
          </div>
          <div class="col-6">
            <label for="novaPeriodo" class="form-label">Período</label>
            <select class="form-select" id="novaPeriodo" name="periodo" required>
              <?php for ($p = 1; $p <= 6; $p++): ?>
                <option value="<?= $p ?>" <?= (int) old('periodo', 1) === $p ? 'selected' : '' ?>><?= $p ?>º Período</option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-el-outline" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-el" data-evitar-duplo>Criar turma</button>
      </div>
    </form>
  </div>
</div>
