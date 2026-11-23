<p class="titulo-pagina">Entregas</p>
<p class="small text-secondary mb-3">Escolha uma atividade para acompanhar o que os alunos enviaram.</p>

<?php if ($atividades === []): ?>
  <div class="text-center py-5">
    <p class="fw-semibold mb-3">Você ainda não criou atividades.</p>
    <a href="/professor/atividades/nova" class="btn btn-el">Criar atividade</a>
  </div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table-el">
      <thead>
        <tr><th>Atividade</th><th>Turmas</th><th>Situação</th><th>Entregas</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($atividades as $atividade): ?>
          <?php $id = (int) $atividade['id']; $ativa = (int) $atividade['ativa'] === 1; ?>
          <tr>
            <td><a class="fw-semibold" href="/professor/entregas/<?= $id ?>"><?= e($atividade['nome']) ?></a></td>
            <td>
              <?php foreach ($turmasPorAtividade[$id] ?? [] as $turma): ?>
                <span class="badge text-bg-light border border-dark fw-normal"><?= e(turma_curta($turma)) ?></span>
              <?php endforeach; ?>
            </td>
            <td class="text-center"><span class="badge <?= $ativa ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $ativa ? 'Ativa' : 'Inativa' ?></span></td>
            <td class="text-center"><?= (int) $atividade['total_entregas'] ?></td>
            <td class="text-center"><a class="btn btn-sm btn-el-outline" href="/professor/entregas/<?= $id ?>">Ver envios</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
