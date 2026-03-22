<?php
session_start();
require_once '../includes/conexao.php';

// Apenas clientes podem agendar por aqui
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['projeto_id']) && !empty($_POST['data_sessao']) && !empty($_POST['hora_sessao'])) {
    $projeto_id = $_POST['projeto_id'];
    $data_sessao = $_POST['data_sessao'];
    $hora_sessao = $_POST['hora_sessao'];

    // Monta a data e hora no padrão correto do banco (YYYY-MM-DD HH:MM:SS)
    $data_hora = $data_sessao . ' ' . $hora_sessao . ':00';

    try {
        // 1. Grava a sessão oficial
        $stmt = $pdo->prepare("INSERT INTO sessao (id_projeto, data_hora, status) VALUES (?, ?, 'Agendado')");
        $stmt->execute([$projeto_id, $data_hora]);

        // 2. Atualiza o status do projeto tirando do "Agendamento Pendente"
        $stmt_proj = $pdo->prepare("UPDATE projeto SET status = 'Em Andamento', motivo_reagendamento = NULL WHERE id_projeto = ?");
        $stmt_proj->execute([$projeto_id]);

        // Manda o cliente de volta pro painel dele para comemorar!
        header("Location: ../pages/agendamentos-cliente.php?sucesso=agendado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agendamentos-cliente.php?erro=bd");
        exit();
    }
}
