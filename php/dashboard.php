<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['mensagem' => 'Acesso negado.']);
    exit;
}

require_once __DIR__ . '/conexao.php';

try {
    $pdo = conectarBanco();

    $totalProdutos = $pdo->query('SELECT COUNT(*) AS total FROM produtos')->fetch()['total'];
    $valorEstoque = $pdo->query('SELECT COALESCE(SUM(valor_unitario * quantidade), 0) AS total FROM produtos')->fetch()['total'];
    $produtosBaixo = $pdo->query('SELECT COUNT(*) AS total FROM produtos WHERE quantidade <= estoque_minimo')->fetch()['total'];
    $estoqueDisponivel = $pdo->query('SELECT COALESCE(SUM(quantidade), 0) AS total FROM produtos')->fetch()['total'];

    $entradasSeteDias = $pdo->query('SELECT COALESCE(SUM(quantidade), 0) AS total FROM movimentacoes WHERE tipo = "entrada" AND data_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetch()['total'];
    $saidasSeteDias = $pdo->query('SELECT COALESCE(SUM(quantidade), 0) AS total FROM movimentacoes WHERE tipo = "saida" AND data_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetch()['total'];

    $movimentosRecentes = $pdo->query('SELECT m.id, m.tipo, m.quantidade, m.data_hora, p.nome FROM movimentacoes m INNER JOIN produtos p ON p.id = m.produto_id ORDER BY m.data_hora DESC LIMIT 10')->fetchAll();

    $produtosAtencao = $pdo->query('SELECT nome, quantidade, estoque_minimo FROM produtos WHERE quantidade <= estoque_minimo ORDER BY quantidade ASC LIMIT 5')->fetchAll();

    echo json_encode([
        'totalProdutos' => (int) $totalProdutos,
        'valorEstoque' => (float) $valorEstoque,
        'produtosBaixo' => (int) $produtosBaixo,
        'estoqueDisponivel' => (int) $estoqueDisponivel,
        'entradasSeteDias' => (int) $entradasSeteDias,
        'saidasSeteDias' => (int) $saidasSeteDias,
        'movimentosRecentes' => $movimentosRecentes,
        'produtosAtencao' => $produtosAtencao
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['mensagem' => 'Erro ao carregar dados do dashboard.']);
}
