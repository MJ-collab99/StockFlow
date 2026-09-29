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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT m.id, m.tipo, m.quantidade, m.data_hora, p.nome AS produto FROM movimentacoes m INNER JOIN produtos p ON p.id = m.produto_id ORDER BY m.data_hora DESC LIMIT 20');
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['mensagem' => 'Método não permitido.']);
    exit;
}

$dados = $_POST;
if (empty($dados)) {
    $input = file_get_contents('php://input');
    parse_str($input, $dados);
}

$produtoId = (int)($dados['produto_id'] ?? 0);
$tipo = strtolower(trim((string)($dados['tipo'] ?? '')));
$quantidade = (int)($dados['quantidade'] ?? 0);

if ($produtoId <= 0 || !in_array($tipo, ['entrada', 'saida'], true) || $quantidade <= 0) {
    http_response_code(400);
    echo json_encode(['mensagem' => 'Informe produto, tipo e quantidade válidos.']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, quantidade FROM produtos WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $produtoId]);
$produto = $stmt->fetch();

if (!$produto) {
    http_response_code(404);
    echo json_encode(['mensagem' => 'Produto não encontrado.']);
    exit;
}

$estoqueAtual = (int) $produto['quantidade'];
$novoEstoque = $estoqueAtual;

if ($tipo === 'entrada') {
    $novoEstoque = $estoqueAtual + $quantidade;
} else {
    $novoEstoque = $estoqueAtual - $quantidade;

    if ($novoEstoque < 0) {
        http_response_code(400);
        echo json_encode(['mensagem' => 'A saída excede a quantidade disponível em estoque.']);
        exit;
    }
}

$update = $pdo->prepare('UPDATE produtos SET quantidade = :quantidade WHERE id = :id');
$update->execute([
    'quantidade' => $novoEstoque,
    'id' => $produtoId
]);

$insert = $pdo->prepare('INSERT INTO movimentacoes (produto_id, tipo, quantidade, data_hora) VALUES (:produto_id, :tipo, :quantidade, NOW())');
$insert->execute([
    'produto_id' => $produtoId,
    'tipo' => $tipo,
    'quantidade' => $quantidade
]);

echo json_encode(['ok' => true, 'mensagem' => 'Movimentação registrada com sucesso.']);
