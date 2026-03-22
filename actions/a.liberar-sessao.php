<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['projeto_id'])) {
    $projeto_id = $_POST['projeto_id'];

    try {
        // 1. Marca qualquer sessão pendente de hoje como Concluída (pra não ficar sessão voando no passado)
        $sql_sessao = "UPDATE sessao SET status = 'Concluído' WHERE id_projeto = ? AND status = 'Agendado'";
        $pdo->prepare($sql_sessao)->execute([$projeto_id]);

        // 2. Volta o projeto para a Ação Requerida do cliente para ele escolher nova data
        $sql_projeto = "UPDATE projeto SET status = 'Agendamento Pendente', motivo_reagendamento = NULL WHERE id_projeto = ?";
        $pdo->prepare($sql_projeto)->execute([$projeto_id]);

        header("Location: ../pages/agenda.php?sucesso=sessao_liberada");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agenda.php?erro=bd");
        exit();
    }
}
