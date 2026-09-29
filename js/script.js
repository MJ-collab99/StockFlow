document.addEventListener('DOMContentLoaded', () => {
  const page = getCurrentPage();

  if (page !== 'index.html') {
    verificarSessao();
  }

  switch (page) {
    case 'index.html':
      initLoginPage();
      break;
    case 'dashboard.html':
      initDashboardPage();
      break;
    case 'produtos.html':
      initProdutosPage();
      break;
    case 'cadastro.html':
      initCadastroPage();
      break;
    case 'movimentacao.html':
      initMovimentacaoPage();
      break;
    default:
      break;
  }
});

function getCurrentPage() {
  return location.pathname.split('/').pop() || 'index.html';
}

function showMessage(elementId, text, type = 'success') {
  const element = document.getElementById(elementId);
  if (!element) return;
  element.textContent = text;
  element.className = 'message ' + type;
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(Number(value || 0));
}

function formatDateTime(dateValue) {
  if (!dateValue) return '-';
  const date = new Date(dateValue);
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
}

async function verificarSessao() {
  try {
    const response = await fetch('php/sessao.php', { cache: 'no-store' });
    const data = await response.json();

    if (!data.logado) {
      window.location.href = 'index.html';
      return;
    }

    const userNameElement = document.getElementById('usuarioLogado');
    if (userNameElement) {
      userNameElement.textContent = data.usuario.nome.split(' ')[0];
    }

    const logoutButton = document.getElementById('logoutButton');
    if (logoutButton) {
      logoutButton.addEventListener('click', async () => {
        await fetch('php/logout.php', { method: 'POST' });
        window.location.href = 'index.html';
      });
    }
  } catch (error) {
    window.location.href = 'index.html';
  }
}

function initLoginPage() {
  const form = document.getElementById('loginForm');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const email = form.email.value.trim();
    const password = form.password.value.trim();

    if (!email || !password) {
      showMessage('loginMessage', 'Informe e-mail e senha.', 'error');
      return;
    }

    const formData = new URLSearchParams();
    formData.append('email', email);
    formData.append('password', password);

    try {
      const response = await fetch('php/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: formData.toString()
      });

      const data = await response.json();

      if (!response.ok || !data.ok) {
        showMessage('loginMessage', data.mensagem || 'Erro ao fazer login.', 'error');
        return;
      }

      showMessage('loginMessage', 'Login realizado com sucesso!', 'success');
      window.location.href = 'dashboard.html';
    } catch (error) {
      showMessage('loginMessage', 'Não foi possível conectar ao servidor.', 'error');
    }
  });
}

async function initDashboardPage() {
  try {
    const response = await fetch('php/dashboard.php', { cache: 'no-store' });
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.mensagem || 'Erro ao carregar dashboard.');
    }

    document.getElementById('totalProdutos').textContent = data.totalProdutos;
    document.getElementById('valorEstoque').textContent = formatCurrency(data.valorEstoque);
    document.getElementById('produtosBaixo').textContent = data.produtosBaixo;
    document.getElementById('estoqueDisponivel').textContent = data.estoqueDisponivel;
    document.getElementById('entradasSeteDias').textContent = data.entradasSeteDias;
    document.getElementById('saidasSeteDias').textContent = data.saidasSeteDias;

    renderMovimentosRecentes(data.movimentosRecentes || []);
    renderProdutosAtencao(data.produtosAtencao || []);
  } catch (error) {
    console.error(error);
  }
}

function renderMovimentosRecentes(movimentos) {
  const container = document.getElementById('tabelaMovimentos');
  if (!container) return;

  if (!movimentos.length) {
    container.innerHTML = '<tr><td colspan="4" class="empty-state">Nenhuma movimentação registrada.</td></tr>';
    return;
  }

  container.innerHTML = movimentos.map((item) => {
    const tipo = item.tipo === 'entrada' ? 'Entrada' : 'Saída';
    const badgeClass = item.tipo === 'entrada' ? 'status-normal' : 'status-critico';

    return `
      <tr>
        <td>${formatDateTime(item.data_hora)}</td>
        <td>${item.nome}</td>
        <td><span class="status-badge ${badgeClass}">${tipo}</span></td>
        <td>${item.quantidade}</td>
      </tr>
    `;
  }).join('');
}

function renderProdutosAtencao(produtos) {
  const container = document.getElementById('listaProdutosAtencao');
  if (!container) return;

  if (!produtos.length) {
    container.innerHTML = '<li class="empty-state">Nenhum produto precisa de atenção.</li>';
    return;
  }

  container.innerHTML = produtos.map((item) => {
    const statusClass = item.quantidade === 0 ? 'status-critico' : 'status-baixo';
    const statusText = item.quantidade === 0 ? 'Estoque crítico' : 'Baixo estoque';

    return `
      <li>
        <span>${item.nome}</span>
        <span class="status-badge ${statusClass}">${statusText}</span>
      </li>
    `;
  }).join('');
}

async function initProdutosPage() {
  const tableBody = document.getElementById('produtosTableBody');
  const searchInput = document.getElementById('pesquisaProduto');
  let listaProdutos = [];

  async function carregarProdutos() {
    try {
      const response = await fetch('php/produtos.php', { cache: 'no-store' });
      const data = await response.json();
      listaProdutos = data || [];
      renderizarTabela(listaProdutos);
    } catch (error) {
      console.error(error);
    }
  }

  function renderizarTabela(produtos) {
    if (!tableBody) return;

    if (!produtos.length) {
      tableBody.innerHTML = '<tr><td colspan="8" class="empty-state">Nenhum produto encontrado.</td></tr>';
      return;
    }

    tableBody.innerHTML = produtos.map((produto) => {
      const status = obterStatus(produto);
      const badgeClass = status.className;

      return `
        <tr>
          <td>${produto.nome}</td>
          <td>${produto.codigo}</td>
          <td>${formatCurrency(produto.valor_unitario)}</td>
          <td>${produto.categoria}</td>
          <td>${produto.quantidade}</td>
          <td>${produto.estoque_minimo}</td>
          <td><span class="status-badge ${badgeClass}">${status.label}</span></td>
          <td>
            <div class="action-group">
              <button class="icon-btn edit" data-action="editar" data-id="${produto.id}">Editar</button>
              <button class="icon-btn delete" data-action="excluir" data-id="${produto.id}">Excluir</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    tableBody.querySelectorAll('button[data-action]').forEach((button) => {
      button.addEventListener('click', () => {
        const id = Number(button.dataset.id);
        const action = button.dataset.action;

        if (action === 'editar') {
          window.location.href = `cadastro.html?id=${id}`;
          return;
        }

        if (action === 'excluir') {
          if (!confirm('Deseja realmente excluir este produto?')) {
            return;
          }

          excluirProduto(id);
        }
      });
    });
  }

  searchInput?.addEventListener('input', (event) => {
    const termo = event.target.value.toLowerCase();
    const filtrados = listaProdutos.filter((produto) => {
      return produto.nome.toLowerCase().includes(termo) || produto.codigo.toLowerCase().includes(termo);
    });
    renderizarTabela(filtrados);
  });

  async function excluirProduto(id) {
    try {
      const response = await fetch('php/produtos.php', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify({ id })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.mensagem || 'Erro ao excluir produto.');
      }

      alert('Produto excluído com sucesso.');
      carregarProdutos();
    } catch (error) {
      alert(error.message || 'Não foi possível excluir o produto.');
    }
  }

  carregarProdutos();
}

function obterStatus(produto) {
  if (Number(produto.quantidade) === 0) {
    return { label: 'Estoque crítico', className: 'status-critico' };
  }

  if (Number(produto.quantidade) <= Number(produto.estoque_minimo)) {
    return { label: 'Baixo estoque', className: 'status-baixo' };
  }

  return { label: 'Normal', className: 'status-normal' };
}

async function initCadastroPage() {
  const form = document.getElementById('produtoForm');
  const codigoInput = document.getElementById('codigoProduto');

  if (!form) return;

  const params = new URLSearchParams(window.location.search);
  const editId = params.get('id');

  if (editId) {
    try {
      const response = await fetch(`php/produtos.php?id=${editId}`);
      const produto = await response.json();

      if (!produto || !produto.id) {
        throw new Error('Produto não encontrado.');
      }

      form.idProduto.value = produto.id;
      form.nome.value = produto.nome;
      form.codigo.value = produto.codigo;
      form.categoria.value = produto.categoria;
      form.valor_unitario.value = produto.valor_unitario;
      form.quantidade.value = produto.quantidade;
      form.estoque_minimo.value = produto.estoque_minimo;
      document.getElementById('tituloFormulario').textContent = 'Editar produto';
      document.getElementById('btnSalvarProduto').textContent = 'Salvar alterações';
    } catch (error) {
      alert('Não foi possível carregar o produto para edição.');
      window.location.href = 'produtos.html';
    }
  } else {
    gerarCodigoProduto();
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const payload = {
      id: form.idProduto.value || null,
      nome: form.nome.value.trim(),
      codigo: form.codigo.value.trim(),
      categoria: form.categoria.value,
      valor_unitario: Number(form.valor_unitario.value),
      quantidade: Number(form.quantidade.value),
      estoque_minimo: Number(form.estoque_minimo.value)
    };

    if (!payload.nome || !payload.categoria || !payload.valor_unitario || Number.isNaN(payload.quantidade) || Number.isNaN(payload.estoque_minimo)) {
      showMessage('cadastroMessage', 'Preencha todos os campos corretamente.', 'error');
      return;
    }

    if (payload.valor_unitario <= 0 || payload.quantidade < 0 || payload.estoque_minimo < 0) {
      showMessage('cadastroMessage', 'Valores inválidos. Verifique o preço, quantidade e estoque mínimo.', 'error');
      return;
    }

    const method = payload.id ? 'PUT' : 'POST';
    const url = 'php/produtos.php';

    try {
      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json; charset=UTF-8'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.mensagem || 'Não foi possível salvar o produto.');
      }

      showMessage('cadastroMessage', payload.id ? 'Produto atualizado com sucesso!' : 'Produto cadastrado com sucesso!', 'success');
      form.reset();
      if (!payload.id) {
        gerarCodigoProduto();
      }
      setTimeout(() => {
        window.location.href = 'produtos.html';
      }, 800);
    } catch (error) {
      showMessage('cadastroMessage', error.message, 'error');
    }
  });

  async function gerarCodigoProduto() {
    try {
      const response = await fetch('php/produtos.php?gerar_codigo=true');
      const data = await response.json();
      if (codigoInput) {
        codigoInput.value = data.codigo;
      }
    } catch (error) {
      if (codigoInput) {
        codigoInput.value = 'PROD-' + Math.floor(Date.now() / 1000);
      }
    }
  }
}

async function initMovimentacaoPage() {
  const form = document.getElementById('movimentacaoForm');
  const selectProduto = document.getElementById('produtoId');

  if (!form) return;

  async function carregarProdutos() {
    try {
      const response = await fetch('php/produtos.php', { cache: 'no-store' });
      const produtos = await response.json();

      if (!produtos.length) {
        selectProduto.innerHTML = '<option value="">Nenhum produto cadastrado</option>';
        return;
      }

      selectProduto.innerHTML = '<option value="">Selecione um produto</option>' + produtos.map((produto) => {
        return `<option value="${produto.id}">${produto.nome} (${produto.quantidade} em estoque)</option>`;
      }).join('');
    } catch (error) {
      console.error(error);
    }
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const payload = {
      produto_id: Number(form.produtoId.value),
      tipo: form.tipo.value,
      quantidade: Number(form.quantidade.value)
    };

    if (!payload.produto_id || !payload.tipo || !payload.quantidade || payload.quantidade <= 0) {
      showMessage('movimentacaoMessage', 'Selecione um produto, informe o tipo e uma quantidade válida.', 'error');
      return;
    }

    try {
      const response = await fetch('php/movimentacoes.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: new URLSearchParams(payload).toString()
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.mensagem || 'Não foi possível registrar a movimentação.');
      }

      showMessage('movimentacaoMessage', 'Movimentação registrada com sucesso!', 'success');
      form.reset();
      carregarProdutos();
    } catch (error) {
      showMessage('movimentacaoMessage', error.message, 'error');
    }
  });

  carregarProdutos();
}
