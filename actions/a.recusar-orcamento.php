<?php
session_start();
require_once '../includes/conexao.php';

// Proteção: Apenas o Artista pode recusar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {

    $id_orcamento = $_POST['orcamento_id'];
    $motivo_recusa = $_POST['motivo_recusa'];

    try {
        // Atualiza o orçamento com o motivo e muda o status
        $sql = "UPDATE orcamento 
                SET status = 'Recusado', motivo_recusa = ? 
                WHERE id_orcamento = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$motivo_recusa, $id_orcamento]);

        // Retorna para a dashboard com sucesso
        header("Location: ../pages/dashboard-artista.php?sucesso=recusado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/dashboard-artista.php?erro=bd");
        exit();
    }
} else {
    header("Location: ../pages/dashboard-artista.php");
    exit();
}
