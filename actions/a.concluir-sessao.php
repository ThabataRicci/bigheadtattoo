<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $sessao_id = $_POST['sessao_id'];

    try {
        // 1. Marca a sessão atual como concluída
        $pdo->prepare("UPDATE sessao SET status = 'Concluído' WHERE id_sessao = ?")->execute([$sessao_id]);

        // 2. Pega o ID do projeto atrelado a esta sessão
        $stmt = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
        $stmt->execute([$sessao_id]);
        $id_projeto = $stmt->fetchColumn();

        // 3. FINALIZA O PROJETO (Vai direto para o Histórico)
        $pdo->prepare("UPDATE projeto SET status = 'Finalizado' WHERE id_projeto = ?")->execute([$id_projeto]);

        header("Location: ../pages/agenda.php?sucesso=concluido");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agenda.php");
        exit();
    }
}
