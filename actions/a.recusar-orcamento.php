<?php
session_start();
require_once '../includes/conexao.php';

// verifica se é o artista q está logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {

    $id_orcamento = $_POST['orcamento_id'];
    $motivo_recusa = $_POST['motivo_recusa'];

    // Captura a página de onde o artista enviou a recusa (Dashboard ou Agenda)
    $origem = $_POST['origem'] ?? 'dashboard-artista.php';

    try {
        // atualiza o orcamento com o motivo da recusa
        $sql = "UPDATE orcamento 
                SET status = 'Recusado', motivo_recusa = ? 
                WHERE id_orcamento = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$motivo_recusa, $id_orcamento]);

        // retorna p/ a página correta
        header("Location: ../pages/" . $origem . "?sucesso=recusado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/" . $origem . "?erro=bd");
        exit();
    }
} else {
    header("Location: ../pages/dashboard-artista.php");
    exit();
}
