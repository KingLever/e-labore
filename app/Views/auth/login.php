<div class="card rounded-5 shadow border-0 w-100" style="max-width: 390px;">
  <div class="card-body p-4">
    <h1 class="h5 text-center mb-4">Entrar no sistema</h1>

    <form action="/login" method="post">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control border-dark" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="Digite seu email" required autofocus>
      </div>

      <div class="mb-2">
        <label for="senha" class="form-label">Senha</label>
        <input type="password" class="form-control border-dark" id="senha" name="senha" placeholder="Digite sua senha" required>
      </div>

      <a href="#aviso-senha" class="d-block small text-secondary mb-1" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="aviso-senha">Esqueceu sua senha? Clique aqui.</a>
      <div class="collapse" id="aviso-senha">
        <p class="small text-secondary mb-0">A recuperação de senha ainda não está disponível. Procure o professor responsável.</p>
      </div>

      <div class="text-center my-4">
        <button type="submit" class="btn btn-outline-dark rounded-pill px-5" data-evitar-duplo>Entrar</button>
      </div>

      <p class="text-center small mb-0">
        Não tem conta? <a href="/cadastro">Cadastre-se</a>
      </p>
    </form>
  </div>
</div>
