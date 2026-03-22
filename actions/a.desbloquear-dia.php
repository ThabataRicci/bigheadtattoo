<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['id_bloqueio'])) {
    $id_artista = $_SESSION['usuario_id'];
    $id_bloqueio = $_POST['id_bloqueio'];

    try {
        $stmt = $pdo->prepare("DELETE FROM bloqueio_agenda WHERE id_bloqueio = ? AND id_artista = ?");
        $stmt->execute([$id_bloqueio, $id_artista]);

        header("Location: ../pages/agenda.php?sucesso=desbloqueado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agenda.php?erro=bd");
        exit();
    }
}
