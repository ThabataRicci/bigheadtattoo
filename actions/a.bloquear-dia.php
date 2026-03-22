<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['data_bloqueio'])) {
    $id_artista = $_SESSION['usuario_id'];
    $data_bloqueio = $_POST['data_bloqueio'];

    try {
        // Verifica se a data já não está bloqueada para evitar duplicação
        $stmt_check = $pdo->prepare("SELECT id_bloqueio FROM bloqueio_agenda WHERE id_artista = ? AND data_bloqueio = ?");
        $stmt_check->execute([$id_artista, $data_bloqueio]);

        if ($stmt_check->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO bloqueio_agenda (id_artista, data_bloqueio) VALUES (?, ?)");
            $stmt->execute([$id_artista, $data_bloqueio]);
        }

        header("Location: ../pages/agenda.php?sucesso=bloqueado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agenda.php?erro=bd");
        exit();
    }
}
