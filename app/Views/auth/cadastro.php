<div class="card rounded-5 shadow border-0 w-100" style="max-width: 390px;">
  <div class="card-body p-4">
    <h1 class="h5 text-center mb-4">Criar conta</h1>

    <form action="/cadastro" method="post">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label for="nome" class="form-label">Nome</label>
        <input type="text" class="form-control border-dark" id="nome" name="nome" value="<?= e(old('nome')) ?>" placeholder="Digite seu nome" maxlength="255" required autofocus>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control border-dark" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="Digite seu email (Gmail)" maxlength="255" required>
      </div>

      <div class="mb-4">
        <label for="senha" class="form-label">Senha</label>
        <input type="password" class="form-control border-dark" id="senha" name="senha" placeholder="Crie uma senha" minlength="10" maxlength="72" required>
        <div class="form-text">De 10 a 72 bytes.</div>
      </div>

      <div class="text-center mb-4">
        <button type="submit" class="btn btn-outline-dark rounded-pill px-5" data-evitar-duplo>Cadastrar</button>
      </div>

      <p class="text-center small mb-0">
        Já tem conta? <a href="/login">Entrar</a>
      </p>
    </form>
  </div>
</div>
