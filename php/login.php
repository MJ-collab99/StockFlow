<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['password'] ?? '');

if (!$email || !$senha) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensagem' => 'Preencha e-mail e senha.']);
    exit;
}

try {
    $pdo = conectarBanco();
    $sql = 'SELECT id, nome, email, senha FROM usuarios WHERE email = :email LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'mensagem' => 'Credenciais inválidas.']);
        exit;
    }

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];

    echo json_encode([
        'ok' => true,
        'mensagem' => 'Login realizado com sucesso.',
        'usuario' => [
            'id' => $usuario['id'],
            'nome' => $usuario['nome']
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensagem' => 'Erro ao autenticar usuário.']);
}
