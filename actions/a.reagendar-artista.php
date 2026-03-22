<?php
session_start();
require_once '../includes/conexao.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') exit();

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $sessao_id = $_POST['sessao_id'];

    // Deixa no mesmo padrão das outras telas
    $motivo = "Reagendado pelo Artista: " . trim($_POST['motivo']);
    try {
        $pdo->prepare("UPDATE sessao SET status = 'Cancelado', motivo_cancelamento = ? WHERE id_sessao = ?")->execute([$motivo, $sessao_id]);
        $id_projeto = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
        $id_projeto->execute([$sessao_id]);
        $id_projeto = $id_projeto->fetchColumn();
        $pdo->prepare("UPDATE projeto SET status = 'Agendamento Pendente', motivo_reagendamento = ? WHERE id_projeto = ?")->execute([$motivo, $id_projeto]);
        header("Location: ../pages/agenda.php?sucesso=reagendado");
    } catch (PDOException $e) {
        header("Location: ../pages/agenda.php");
    }
}
