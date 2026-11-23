<?php
$editando = $atividade !== null;
$acao = $editando ? '/professor/atividades/' . (int) $atividade['id'] . '/editar' : '/professor/atividades';
$nome = old('nome', $atividade['nome'] ?? '');
$descricao = old('descricao', $atividade['descricao'] ?? '');
$marcadas = array_map('intval', (array) old('turmas', $selecionadas));
$temAntigo = old('nome', null) !== null;
$ativa = $temAntigo ? old('ativa', null) !== null : ($atividade === null || (int) $atividade['ativa'] === 1);
?>
<p class="small mb-2"><a href="/professor/atividades" class="text-dark"><i class="bi bi-arrow-left"></i> Atividades</a></p>
<p class="titulo-pagina"><?= $editando ? 'Editar atividade' : 'Nova atividade' ?></p>

<?php if ($turmas === []): ?>
  <div class="alert alert-warning">
    Você precisa ter pelo menos uma turma para distribuir uma atividade.
    <a href="/professor/turmas" class="alert-link">Criar turma</a>
  </div>
<?php else: ?>
  <form method="post" action="<?= e($acao) ?>" class="card border-dark" style="max-width: 720px;">
    <?= csrf_field() ?>
    <div class="card-body p-4">
      <div class="mb-3">
        <label for="nome" class="form-label">Nome da atividade</label>
        <input type="text" class="form-control" id="nome" name="nome" value="<?= e($nome) ?>" maxlength="255" required>
      </div>

      <div class="mb-3">
        <label for="descricao" class="form-label">Descrição / orientações <span class="text-secondary">(opcional)</span></label>
        <textarea class="form-control" id="descricao" name="descricao" rows="5" maxlength="5000"><?= e($descricao) ?></textarea>
      </div>

      <fieldset class="mb-3">
        <legend class="form-label fs-6">Turmas que terão acesso</legend>
        <?php foreach ($turmas as $turma): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="turmas[]" id="turma<?= (int) $turma['id'] ?>" value="<?= (int) $turma['id'] ?>"
                   <?= in_array((int) $turma['id'], $marcadas, true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="turma<?= (int) $turma['id'] ?>"><?= e(turma_label($turma)) ?></label>
          </div>
        <?php endforeach; ?>
      </fieldset>

      <div class="form-check form-switch mb-4">
        <input class="form-check-input" type="checkbox" role="switch" id="ativa" name="ativa" value="1" <?= $ativa ? 'checked' : '' ?>>
        <label class="form-check-label" for="ativa">Atividade ativa (os alunos podem enviar entregas)</label>
      </div>

      <div class="d-flex gap-2">
        <a href="/professor/atividades" class="btn btn-el-outline px-4">Cancelar</a>
        <button type="submit" class="btn btn-el px-4" data-evitar-duplo><?= $editando ? 'Salvar alterações' : 'Criar atividade' ?></button>
      </div>
    </div>
  </form>
<?php endif; ?>
