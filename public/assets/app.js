// Preenche os modais (editar/confirmar) com os dados do botão que os abriu.
// O botão usa: data-bs-toggle="modal" data-bs-target="#idDoModal" data-acao="/rota" data-<campo>="valor".
// Dentro do modal: <input data-campo="nome"> recebe o valor; <span data-texto-de="nome"> recebe o texto.
document.addEventListener('show.bs.modal', (evento) => {
  const origem = evento.relatedTarget;

  if (!origem || !origem.dataset) {
    return;
  }

  const modal = evento.target;
  const formulario = modal.querySelector('form');

  if (formulario && origem.dataset.acao) {
    formulario.action = origem.dataset.acao;
  }

  modal.querySelectorAll('[data-campo]').forEach((campo) => {
    campo.value = origem.dataset[campo.dataset.campo] ?? '';
  });

  modal.querySelectorAll('[data-texto-de]').forEach((elemento) => {
    elemento.textContent = origem.dataset[elemento.dataset.textoDe] ?? '';
  });
});

// Evita envio duplo: desativa o botão de envio depois do primeiro clique.
document.addEventListener('submit', (evento) => {
  const botao = evento.target.querySelector('[data-evitar-duplo]');

  if (botao) {
    setTimeout(() => { botao.disabled = true; }, 0);
  }
});
