<?php $turmaId = (int) $turma['id']; ?>
<p class="small mb-2"><a href="/professor/turmas" class="text-dark"><i class="bi bi-arrow-left"></i> Turmas</a></p>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <p class="titulo-pagina mb-0"><?= e(turma_curta($turma)) ?></p>
    <small class="text-secondary"><?= (int) $turma['periodo'] ?>º Período · <?= count($alunos) ?> aluno(s)</small>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-el" data-bs-toggle="modal" data-bs-target="#modalEditarTurma"
            data-acao="/professor/turmas/<?= $turmaId ?>/editar"
            data-curso="<?= e($turma['curso']) ?>" data-ano="<?= e($turma['ano']) ?>" data-periodo="<?= (int) $turma['periodo'] ?>">
      <i class="bi bi-pencil me-1"></i>Editar turma
    </button>
    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalConfirmar"
            data-acao="/professor/turmas/<?= $turmaId ?>/excluir"
            data-titulo="Excluir turma"
            data-texto="Você está prestes a excluir a turma:"
            data-nome="<?= e(turma_label($turma)) ?>"
            data-aviso="Os vínculos dos alunos com esta turma e a distribuição das atividades para ela serão removidos. As contas dos alunos continuam existindo. Essa ação não pode ser desfeita."
            data-botao="Excluir turma">
      <i class="bi bi-trash me-1"></i>Excluir turma
    </button>
  </div>
</div>

<form method="post" action="/professor/turmas/<?= $turmaId ?>/alunos" class="row g-2 align-items-end mb-4">
  <?= csrf_field() ?>
  <div class="col-12 col-md-6 col-lg-5">
    <label for="emailAluno" class="form-label mb-1">Adicionar aluno pelo e-mail da conta</label>
    <input type="email" class="form-control" id="emailAluno" name="email" list="sugestoesAlunos" placeholder="aluno@gmail.com" required>
    <datalist id="sugestoesAlunos">
      <?php foreach ($sugestoes as $email): ?>
        <option value="<?= e($email) ?>"></option>
      <?php endforeach; ?>
    </datalist>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-el" data-evitar-duplo><i class="bi bi-person-plus me-1"></i>Adicionar</button>
  </div>
</form>

<?php if ($alunos === []): ?>
  <p class="text-center fw-semibold py-4">Esta turma ainda não tem alunos.</p>
<?php else: ?>
  <div class="table-responsive">
    <table class="table-el">
      <thead>
        <tr><th>Nome</th><th>E-mail</th><th style="width: 190px;">Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($alunos as $aluno): ?>
          <tr>
            <td><?= e($aluno['nome']) ?></td>
            <td><?= e($aluno['email']) ?></td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-el-outline" data-bs-toggle="modal" data-bs-target="#modalEditarAluno"
                      data-acao="/professor/turmas/<?= $turmaId ?>/alunos/<?= (int) $aluno['id'] ?>/editar"
                      data-nome="<?= e($aluno['nome']) ?>" data-email="<?= e($aluno['email']) ?>">
                <i class="bi bi-pencil"></i> Editar
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalConfirmar"
                      data-acao="/professor/turmas/<?= $turmaId ?>/alunos/<?= (int) $aluno['id'] ?>/remover"
                      data-titulo="Remover aluno da turma"
                      data-texto="Você está prestes a remover da turma <?= e(turma_curta($turma)) ?> o aluno:"
                      data-nome="<?= e($aluno['nome'] . ' (' . $aluno['email'] . ')') ?>"
                      data-aviso="A conta do aluno não será apagada, mas ele deixa de ver as atividades desta turma. Os envios que ele já fez continuam guardados."
                      data-botao="Remover aluno">
                <i class="bi bi-person-dash"></i> Remover
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<div class="modal fade" id="modalEditarTurma" tabindex="-1" aria-labelledby="tituloEditarTurma" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="tituloEditarTurma">Editar turma</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="editCurso" class="form-label">Curso</label>
          <input type="text" class="form-control" id="editCurso" name="curso" data-campo="curso" maxlength="100" required>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label for="editAno" class="form-label">Ano</label>
            <input type="text" class="form-control" id="editAno" name="ano" data-campo="ano" pattern="\d{4}/[12]" required>
          </div>
          <div class="col-6">
            <label for="editPeriodo" class="form-label">Período</label>
            <select class="form-select" id="editPeriodo" name="periodo" data-campo="periodo" required>
              <?php for ($p = 1; $p <= 10; $p++): ?>
                <option value="<?= $p ?>"><?= $p ?>º Período</option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-el-outline" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-el" data-evitar-duplo>Salvar</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalEditarAluno" tabindex="-1" aria-labelledby="tituloEditarAluno" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="tituloEditarAluno">Editar aluno</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="editNomeAluno" class="form-label">Nome</label>
          <input type="text" class="form-control" id="editNomeAluno" name="nome" data-campo="nome" maxlength="255" required>
        </div>
        <div>
          <label for="editEmailAluno" class="form-label">E-mail</label>
          <input type="email" class="form-control" id="editEmailAluno" name="email" data-campo="email" maxlength="255" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-el-outline" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-el" data-evitar-duplo>Salvar</button>
      </div>
    </form>
  </div>
</div>
