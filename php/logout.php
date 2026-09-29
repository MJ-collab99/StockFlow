<?php
session_start();

$_SESSION = [];
if (session_id() !== '') {
    session_destroy();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'mensagem' => 'Sessão encerrada.']);
