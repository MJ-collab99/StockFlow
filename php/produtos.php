<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['mensagem' => 'Acesso negado.']);
    exit;
}

$pdo = conectarBanco();

function statusProduto($quantidade, $estoqueMinimo)
{
    if ((int)$quantidade === 0) {
        return 'estoque crítico';
    }

    if ((int)$quantidade <= (int)$estoqueMinimo) {
        return 'baixo estoque';
    }

    return 'normal';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['gerar_codigo'])) {
        $sql = 'SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 6) AS UNSIGNED)), 0) + 1 AS proximo FROM produtos';
        $proximo = $pdo->query($sql)->fetchColumn();
        $codigo = 'PROD-' . str_pad((string) $proximo, 4, '0', STR_PAD_LEFT);
        echo json_encode(['codigo' => $codigo]);
        exit;
    }

    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $produto = $stmt->fetch();

        if (!$produto) {
            http_response_code(404);
            echo json_encode(['mensagem' => 'Produto não encontrado.']);
            exit;
        }

        echo json_encode($produto);
        exit;
    }

    $stmt = $pdo->query('SELECT * FROM produtos ORDER BY nome ASC');
    $produtos = $stmt->fetchAll();

    foreach ($produtos as &$produto) {
        $produto['status'] = statusProduto($produto['quantidade'], $produto['estoque_minimo']);
    }

    echo json_encode($produtos);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    parse_str($input, $data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string)($data['nome'] ?? ''));
    $codigo = trim((string)($data['codigo'] ?? ''));
    $categoria = trim((string)($data['categoria'] ?? ''));
    $valor = (float)($data['valor_unitario'] ?? 0);
    $quantidade = (int)($data['quantidade'] ?? 0);
    $estoqueMinimo = (int)($data['estoque_minimo'] ?? 0);

    if ($nome === '' || $categoria === '' || $valor <= 0 || $quantidade < 0 || $estoqueMinimo < 0) {
        http_response_code(400);
        echo json_encode(['mensagem' => 'Dados inválidos para cadastro do produto.']);
        exit;
    }

    if ($codigo === '') {
        $codigo = 'PROD-' . str_pad((string)((int)$pdo->query('SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 6) AS UNSIGNED)), 0) + 1 FROM produtos')->fetchColumn()), 4, '0', STR_PAD_LEFT);
    }

    $verifica = $pdo->prepare('SELECT id FROM produtos WHERE codigo = :codigo LIMIT 1');
    $verifica->execute(['codigo' => $codigo]);
    if ($verifica->fetch()) {
        http_response_code(409);
        echo json_encode(['mensagem' => 'Já existe um produto com este código.']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO produtos (nome, codigo, valor_unitario, categoria, quantidade, estoque_minimo, criado_em) VALUES (:nome, :codigo, :valor_unitario, :categoria, :quantidade, :estoque_minimo, NOW())');
    $stmt->execute([
        'nome' => $nome,
        'codigo' => $codigo,
        'valor_unitario' => $valor,
        'categoria' => $categoria,
        'quantidade' => $quantidade,
        'estoque_minimo' => $estoqueMinimo
    ]);

    echo json_encode(['mensagem' => 'Produto cadastrado com sucesso.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = (int)($data['id'] ?? 0);
    $nome = trim((string)($data['nome'] ?? ''));
    $categoria = trim((string)($data['categoria'] ?? ''));
    $valor = (float)($data['valor_unitario'] ?? 0);
    $quantidade = (int)($data['quantidade'] ?? 0);
    $estoqueMinimo = (int)($data['estoque_minimo'] ?? 0);

    if ($id <= 0 || $nome === '' || $categoria === '' || $valor <= 0 || $quantidade < 0 || $estoqueMinimo < 0) {
        http_response_code(400);
        echo json_encode(['mensagem' => 'Dados inválidos para atualização do produto.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE produtos SET nome = :nome, categoria = :categoria, valor_unitario = :valor_unitario, quantidade = :quantidade, estoque_minimo = :estoque_minimo WHERE id = :id');
    $stmt->execute([
        'nome' => $nome,
        'categoria' => $categoria,
        'valor_unitario' => $valor,
        'quantidade' => $quantidade,
        'estoque_minimo' => $estoqueMinimo,
        'id' => $id
    ]);

    echo json_encode(['mensagem' => 'Produto atualizado com sucesso.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = (int)($data['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['mensagem' => 'Produto inválido.']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
    $stmt->execute(['id' => $id]);

    echo json_encode(['mensagem' => 'Produto excluído com sucesso.']);
    exit;
}

http_response_code(405);
echo json_encode(['mensagem' => 'Método não suportado.']);
