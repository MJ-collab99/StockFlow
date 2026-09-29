# StockFlow – Seu estoque sob controle

Sistema web simples para controlar produtos, movimentações e estoque mínimo, desenvolvido com HTML, CSS, JavaScript, PHP e MySQL.

## 1. Estrutura das pastas

- `index.html`: tela de login.
- `dashboard.html`: página principal com indicadores do estoque.
- `produtos.html`: listagem de produtos e pesquisa.
- `cadastro.html`: cadastro/edição de produtos.
- `movimentacao.html`: registro de entrada e saída de produto.
- `css/`: arquivos de estilo.
- `js/`: scripts JavaScript da interface.
- `php/`: scripts de processamento no servidor.
- `banco/`: arquivo de importação do banco de dados.

## 2. Arquivos principais

- `php/conexao.php`: conecta ao MySQL usando PDO.
- `php/login.php`: autentica o usuário e inicia a sessão.
- `php/logout.php`: encerra a sessão.
- `php/dashboard.php`: retorna dados consolidados para o dashboard.
- `php/produtos.php`: lista, cadastra, edita e exclui produtos.
- `php/movimentacoes.php`: registra entradas e saídas no estoque.
- `banco/stockflow.sql`: estrutura do banco com tabelas e dados iniciais.

## 3. Configuração do banco

1. Abra o XAMPP e inicie Apache e MySQL.
2. Acesse `http://localhost/phpmyadmin`.
3. Crie um banco chamado `stockflow`.
4. Importe o arquivo `banco/stockflow.sql`.
5. Certifique-se de que a conexão em `php/conexao.php` usa as configurações corretas do seu ambiente.

A configuração padrão usada neste projeto é:

- Host: `localhost`
- Banco: `stockflow`
- Usuário: `root`
- Senha: vazia

## 4. Como executar o projeto

1. Copie a pasta do projeto para a pasta `htdocs` do XAMPP.
2. Inicie o Apache.
3. Acesse no navegador:

   `http://localhost/stockflowPJ/`

Se a pasta do projeto estiver em outro nome, substitua `stockflowPJ` pelo nome correto da pasta.

## 5. Como testar o login

O sistema vem com um usuário padrão já cadastrado:

- E-mail: `admin@stockflow.com`
- Senha: `admin123`

Acesse a página inicial, informe esses dados e faça login.

## 6. Como cadastrar um produto

1. Depois do login, acesse a página `cadastro.html`.
2. Preencha os campos: nome, categoria, valor unitário, quantidade inicial e estoque mínimo.
3. O código do produto será gerado automaticamente pelo sistema.
4. Clique em `Salvar produto`.

## 7. Como registrar uma entrada

1. Acesse `movimentacao.html`.
2. Selecione o produto.
3. Escolha o tipo `Entrada`.
4. Informe a quantidade.
5. Clique em `Registrar movimentação`.

## 8. Como registrar uma saída

1. Acesse `movimentacao.html`.
2. Selecione o produto.
3. Escolha o tipo `Saída`.
4. Informe a quantidade.
5. O sistema bloqueia saídas que fariam o estoque ficar negativo.

## 9. Como verificar o estoque baixo

Na página de produtos e no dashboard, os itens com quantidade menor ou igual ao estoque mínimo aparecem como `Baixo estoque` ou `Estoque crítico`.

A regra usada é:

- `quantidade <= estoque_minimo` → baixa quantidade
- `quantidade == 0` → estoque crítico

## 10. Observações finais

Este projeto foi estruturado para ser simples e fácil de explicar em sala de aula, mantendo a separação correta entre:

- HTML para estrutura;
- CSS para visual;
- JavaScript para interações no navegador;
- PHP para autenticação, banco e regras do sistema;
- MySQL para armazenamento dos dados.
