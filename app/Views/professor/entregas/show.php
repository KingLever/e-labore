<p class="small mb-2"><a href="/professor/entregas" class="text-dark"><i class="bi bi-arrow-left"></i> Entregas</a></p>

<div class="mb-4">
  <p class="titulo-pagina mb-1"><?= e($atividade['nome']) ?>
    <span class="badge <?= (int) $atividade['ativa'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?> ms-1"><?= (int) $atividade['ativa'] === 1 ? 'Ativa' : 'Inativa' ?></span>
  </p>
  <?php foreach ($turmas as $turma): ?>
    <span class="badge text-bg-light border border-dark fw-normal"><?= e(turma_label($turma)) ?></span>
  <?php endforeach; ?>
  <?php if (!empty($atividade['descricao'])): ?>
    <p class="small mt-2 mb-0" style="white-space: pre-line;"><?= e($atividade['descricao']) ?></p>
  <?php endif; ?>
</div>

<h2 class="h6 mb-2">Resumo por aluno</h2>
<?php if ($resumo === []): ?>
  <p class="small text-secondary">Nenhum aluno vinculado às turmas desta atividade.</p>
<?php else: ?>
  <div class="table-responsive mb-4">
    <table class="table-el">
      <thead><tr><th>Aluno</th><th>E-mail</th><th>Entregas</th><th>Horas</th><th>Situação</th></tr></thead>
      <tbody>
        <?php foreach ($resumo as $linha): ?>
          <tr>
            <td><?= e($linha['nome']) ?></td>
            <td><?= e($linha['email']) ?></td>
            <td class="text-center"><?= (int) $linha['total_entregas'] ?></td>
            <td class="text-center"><?= e(fmt_duracao((int) $linha['segundos'])) ?></td>
            <td class="text-center">
              <?php if ((int) $linha['total_entregas'] > 0): ?>
                <span class="badge text-bg-success">Enviou</span>
              <?php else: ?>
                <span class="badge text-bg-warning">Sem envios</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<h2 class="h6 mb-2">Registros enviados</h2>
<?php if ($entregas === []): ?>
  <p class="text-center fw-semibold py-4">Nenhum aluno enviou entregas nesta atividade ainda.</p>
<?php else: ?>
  <div class="table-responsive">
    <table class="table-el">
      <thead><tr><th>Aluno</th><th>Título</th><th>Data</th><th>Horário</th><th>Duração</th><th>Anexos</th></tr></thead>
      <tbody>
        <?php foreach ($entregas as $entrega): ?>
          <tr>
            <td><?= e($entrega['aluno_nome']) ?></td>
            <td>
              <strong><?= e($entrega['titulo']) ?></strong>
              <?php if (!empty($entrega['descricao'])): ?>
                <div class="small" style="white-space: pre-line;"><?= e($entrega['descricao']) ?></div>
              <?php endif; ?>
            </td>
            <td class="text-center"><?= e(fmt_data($entrega['data'])) ?></td>
            <td class="text-center"><?= e(fmt_hora($entrega['hora_inicio'])) ?> – <?= e(fmt_hora($entrega['hora_fim'])) ?></td>
            <td class="text-center"><?= e(fmt_duracao((int) $entrega['segundos'])) ?></td>
            <td>
              <?php if ($entrega['arquivos'] === []): ?>
                <span class="small text-dark-emphasis">Sem anexos</span>
              <?php endif; ?>
              <?php foreach ($entrega['arquivos'] as $arquivo): ?>
                <div>
                  <a href="/arquivos/<?= (int) $arquivo['id'] ?>" target="_blank" rel="noopener" class="small">
                    <i class="bi bi-paperclip"></i><?= e($arquivo['nome_arquivo']) ?>
                  </a>
                  <span class="small text-dark-emphasis">(<?= e(fmt_tamanho((int) $arquivo['tamanho'])) ?>)</span>
                </div>
              <?php endforeach; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
