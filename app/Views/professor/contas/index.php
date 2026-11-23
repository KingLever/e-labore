<p class="titulo-pagina">Contas</p>
<p class="small text-secondary mb-3">Contas (e-mails) de alunos cadastradas na plataforma e as suas turmas. Vincule uma conta a uma turma para que o aluno veja as atividades dela.</p>

<form method="get" action="/professor/contas" class="row g-2 mb-3">
  <div class="col-12 col-md-6 col-lg-4">
    <input type="search" class="form-control" name="q" value="<?= e($busca) ?>" placeholder="Buscar por nome ou e-mail" aria-label="Buscar conta">
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-el"><i class="bi bi-search me-1"></i>Buscar</button>
    <?php if ($busca !== ''): ?>
      <a href="/professor/contas" class="btn btn-el-outline">Limpar</a>
    <?php endif; ?>
  </div>
</form>

<?php if ($contas === []): ?>
  <p class="text-center fw-semibold py-4"><?= $busca !== '' ? 'Nenhuma conta encontrada.' : 'Ainda não há contas de alunos cadastradas.' ?></p>
<?php else: ?>
  <div class="table-responsive">
    <table class="table-el">
      <thead>
        <tr><th>Nome</th><th>E-mail</th><th>Suas turmas</th><th style="width: 290px;">Vincular a uma turma</th></tr>
      </thead>
      <tbody>
        <?php foreach ($contas as $conta): ?>
          <?php
          $contaId = (int) $conta['id'];
          $vinculadas = $vinculos[$contaId] ?? [];
          $idsVinculados = array_map(static fn (array $t): int => (int) $t['id'], $vinculadas);
          $livres = array_filter($turmas, static fn (array $t): bool => !in_array((int) $t['id'], $idsVinculados, true));
          ?>
          <tr>
            <td><?= e($conta['nome']) ?></td>
            <td><?= e($conta['email']) ?></td>
            <td>
              <?php if ($vinculadas === []): ?>
                <span class="small text-dark-emphasis">Sem turma</span>
              <?php endif; ?>
              <?php foreach ($vinculadas as $turma): ?>
                <button type="button" class="selo-turma" data-bs-toggle="modal" data-bs-target="#modalConfirmar"
                        title="Remover vínculo"
                        data-acao="/professor/contas/<?= $contaId ?>/desvincular"
                        data-turma_id="<?= (int) $turma['id'] ?>"
                        data-titulo="Remover vínculo"
                        data-texto="Você está prestes a desvincular a conta <?= e($conta['email']) ?> da turma:"
                        data-nome="<?= e(turma_label($turma)) ?>"
                        data-aviso="O aluno deixa de ver as atividades desta turma. Os envios que ele já fez continuam guardados."
                        data-botao="Remover vínculo">
                  <?= e(turma_curta($turma)) ?> <i class="bi bi-x-lg"></i>
                </button>
              <?php endforeach; ?>
            </td>
            <td>
              <?php if ($turmas === []): ?>
                <a href="/professor/turmas" class="small text-dark">Crie uma turma primeiro</a>
              <?php elseif ($livres === []): ?>
                <span class="small text-dark-emphasis">Já está em todas as suas turmas</span>
              <?php else: ?>
                <form method="post" action="/professor/contas/<?= $contaId ?>/vincular" class="d-flex gap-1">
                  <?= csrf_field() ?>
                  <select name="turma_id" class="form-select form-select-sm" aria-label="Turma" required>
                    <?php foreach ($livres as $turma): ?>
                      <option value="<?= (int) $turma['id'] ?>"><?= e(turma_label($turma)) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-sm btn-el">Vincular</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
