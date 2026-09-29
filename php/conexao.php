<?php

function conectarBanco() {
    $host = 'localhost';
    $banco = 'stockflow';
    $usuario = 'root';
    $senha = '';

    $dsn = 'mysql:host=' . $host . ';dbname=' . $banco . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, $usuario, $senha, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['mensagem' => 'Erro ao conectar ao banco de dados.']);
        exit;
    }
}
