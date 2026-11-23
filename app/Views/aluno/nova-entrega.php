<?php $atividadeId = (int) $atividade['id']; ?>
<p class="small mb-2"><a href="/aluno/atividades/<?= $atividadeId ?>" class="text-dark"><i class="bi bi-arrow-left"></i> <?= e($atividade['nome']) ?></a></p>
<h1 class="h6 fw-bold text-center mb-3">Enviar atividade</h1>

<form method="post" action="/aluno/atividades/<?= $atividadeId ?>/entregas" enctype="multipart/form-data" class="caixa-envio mx-auto" style="max-width: 920px;" id="formEnvio">
  <?= csrf_field() ?>
  <div class="row g-0">
    <div class="col-md-6 coluna-esquerda">
      <div class="mb-3">
        <input type="text" class="form-control" name="titulo" value="<?= e(old('titulo')) ?>" placeholder="Título" maxlength="255" aria-label="Título" required>
      </div>

      <div class="mb-3">
        <textarea class="form-control" name="descricao" rows="3" placeholder="Descrição da atividade (opcional)" maxlength="5000" aria-label="Descrição da atividade"><?= e(old('descricao')) ?></textarea>
      </div>

      <div class="mb-3">
        <label for="data" class="form-label small mb-1"><i class="bi bi-calendar3"></i> Data em que a atividade foi realizada</label>
        <input type="date" class="form-control" id="data" name="data" value="<?= e(old('data')) ?>" max="<?= e(date('Y-m-d')) ?>" required>
      </div>

      <div class="row g-2 mb-4">
        <div class="col-6">
          <label for="hora_inicio" class="form-label small mb-1"><i class="bi bi-clock"></i> Horário inicial</label>
          <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" value="<?= e(old('hora_inicio')) ?>" required>
        </div>
        <div class="col-6">
          <label for="hora_fim" class="form-label small mb-1"><i class="bi bi-clock"></i> Horário final</label>
          <input type="time" class="form-control" id="hora_fim" name="hora_fim" value="<?= e(old('hora_fim')) ?>" required>
        </div>
      </div>

      <div class="d-flex gap-3">
        <a href="/aluno/atividades/<?= $atividadeId ?>" class="btn btn-el-outline flex-fill py-2">Cancelar</a>
        <button type="submit" class="btn btn-el flex-fill py-2" id="botaoEnviar" data-evitar-duplo>Enviar</button>
      </div>
    </div>

    <div class="col-md-6 coluna-direita">
      <label for="anexos" class="zona-arquivos h-100 m-0" id="zonaArquivos">
        <i class="bi bi-images"></i>
        <small class="text-secondary mb-3">tamanho máximo: <?= (int) $maxMb ?>MB por arquivo</small>
        <span class="small">Arraste seus arquivos aqui ou clique<br>para selecionar os arquivos</span>
        <span class="small text-secondary mt-2">PDF, JPG, PNG, DOC ou DOCX · até <?= (int) $maxArquivos ?> arquivos</span>
        <input type="file" class="visually-hidden" id="anexos" name="anexos[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
      </label>
      <ul class="list-unstyled px-3 pb-3 mb-0" id="listaArquivos" aria-live="polite"></ul>
    </div>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const zona = document.getElementById('zonaArquivos');
  const entrada = document.getElementById('anexos');
  const lista = document.getElementById('listaArquivos');
  const limite = <?= (int) $maxMb ?> * 1024 * 1024;
  const maximo = <?= (int) $maxArquivos ?>;
  const acumulado = new DataTransfer();

  const desenhar = () => {
    lista.innerHTML = '';

    Array.from(acumulado.files).forEach((arquivo, indice) => {
      const item = document.createElement('li');
      item.className = 'd-flex justify-content-between align-items-center small py-1 border-bottom';

      const nome = document.createElement('span');
      nome.textContent = arquivo.name + ' (' + (arquivo.size / 1048576).toFixed(1) + ' MB)';

      if (arquivo.size > limite) {
        nome.classList.add('text-danger');
        nome.textContent += ' - maior que o limite';
      }

      const remover = document.createElement('button');
      remover.type = 'button';
      remover.className = 'btn btn-sm btn-link text-danger p-0 ms-2';
      remover.setAttribute('aria-label', 'Remover ' + arquivo.name);
      remover.innerHTML = '<i class="bi bi-x-lg"></i>';
      remover.addEventListener('click', () => {
        acumulado.items.remove(indice);
        entrada.files = acumulado.files;
        desenhar();
      });

      item.append(nome, remover);
      lista.appendChild(item);
    });

    if (acumulado.files.length > maximo) {
      const aviso = document.createElement('li');
      aviso.className = 'small text-danger pt-1';
      aviso.textContent = 'Selecione no máximo ' + maximo + ' arquivos.';
      lista.appendChild(aviso);
    }
  };

  const adicionar = (arquivos) => {
    Array.from(arquivos).forEach((arquivo) => acumulado.items.add(arquivo));
    entrada.files = acumulado.files;
    desenhar();
  };

  entrada.addEventListener('change', () => adicionar(entrada.files));

  ['dragenter', 'dragover'].forEach((nomeEvento) => {
    zona.addEventListener(nomeEvento, (evento) => {
      evento.preventDefault();
      zona.classList.add('arrastando');
    });
  });

  ['dragleave', 'drop'].forEach((nomeEvento) => {
    zona.addEventListener(nomeEvento, () => zona.classList.remove('arrastando'));
  });

  zona.addEventListener('drop', (evento) => {
    evento.preventDefault();
    adicionar(evento.dataTransfer.files);
  });
});
</script>
