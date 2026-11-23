<?php

use ELabore\Support\Flash;

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
<body class="bg-secondary-subtle">
  <div class="container min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
    <div class="text-center mb-3">
      <img src="/assets/logo.png" alt="E-Labora" class="logo-login">
    </div>

    <div class="w-100" style="max-width: 390px;">
      <?php foreach ($mensagens as $mensagem): ?>
        <div class="alert alert-<?= e($mensagem['tipo']) ?> alert-dismissible fade show py-2 small" role="alert">
          <?= e($mensagem['mensagem']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
      <?php endforeach; ?>
    </div>

    <?= $content ?>
  </div>
</body>
</html>
