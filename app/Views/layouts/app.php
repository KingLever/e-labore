<?php

use ELabore\Support\Auth;
use ELabore\Support\Flash;

$usuario = Auth::usuario();
$inicio = $usuario !== null ? Auth::inicio($usuario['perfil']) : '/login';
$mensagens = Flash::pull();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'E-Labora') ?> | E-Labora</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/assets/app.css" rel="stylesheet">
  <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script defer src="/assets/app.js"></script>
</head>
<body>
  <header class="topbar d-flex align-items-center justify-content-between px-3 px-md-4 py-2">
    <a href="<?= e($inicio) ?>" aria-label="Página inicial"><img src="/assets/logo.png" alt="E-Labora" class="logo"></a>

    <?php if ($usuario !== null): ?>
      <div class="dropdown">
        <button class="btn btn-link text-dark p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu do usuário">
          <i class="bi bi-person-circle fs-2"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <span class="dropdown-item-text">
              <strong><?= e($usuario['nome']) ?></strong><br>
              <small class="text-secondary"><?= e($usuario['email']) ?> · <?= $usuario['perfil'] === 'professor' ? 'Professor' : 'Aluno' ?></small>
            </span>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="post" action="/logout">
              <?= csrf_field() ?>
              <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Sair</button>
            </form>
          </li>
        </ul>
      </div>
    <?php endif; ?>
  </header>

  <main class="container-xl py-4">
    <?php foreach ($mensagens as $mensagem): ?>
      <div class="alert alert-<?= e($mensagem['tipo']) ?> alert-dismissible fade show" role="alert">
        <?= e($mensagem['mensagem']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
      </div>
    <?php endforeach; ?>

    <?= $content ?>
  </main>

  <!-- Janela de confirmação usada antes de qualquer exclusão -->
  <div class="modal fade" id="modalConfirmar" tabindex="-1" aria-labelledby="confirmarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form method="post" class="modal-content">
        <?= csrf_field() ?>
        <input type="hidden" name="turma_id" data-campo="turma_id" value="">
        <div class="modal-header">
          <h2 class="modal-title fs-5" id="confirmarTitulo"><span data-texto-de="titulo">Confirmar</span></h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2" data-texto-de="texto"></p>
          <p class="fw-semibold mb-2" data-texto-de="nome"></p>
          <p class="small text-danger mb-0" data-texto-de="aviso"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-el-outline" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger"><span data-texto-de="botao">Excluir</span></button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
