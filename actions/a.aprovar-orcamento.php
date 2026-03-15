<?php
session_start();
require_once '../includes/conexao.php';

// Proteção: Apenas o Artista pode aprovar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {

    $id_orcamento = $_POST['orcamento_id'];
    $estimativa_tempo = $_POST['estimativa_tempo'];
    $qtd_sessoes = $_POST['qtd_sessoes'];

    try {
        // Atualiza o orçamento com as estimativas e muda o status
        $sql = "UPDATE orcamento 
                SET status = 'Aprovado', estimativa_tempo = ?, qtd_sessoes = ? 
                WHERE id_orcamento = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$estimativa_tempo, $qtd_sessoes, $id_orcamento]);

        // Retorna para a dashboard com sucesso
        header("Location: ../pages/dashboard-artista.php?sucesso=aprovado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/dashboard-artista.php?erro=bd");
        exit();
    }
} else {
    header("Location: ../pages/dashboard-artista.php");
    exit();
}
