<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {
    $id_orcamento = $_POST['orcamento_id'];

    try {
        // Pega os dados aprovados pelo artista
        $stmt = $pdo->prepare("SELECT id_usuario, titulo_sugerido FROM orcamento WHERE id_orcamento = ?");
        $stmt->execute([$id_orcamento]);
        $orc = $stmt->fetch();

        // 1. Cria oficialmente a tatuagem (Projeto)
        $sql_projeto = "INSERT INTO projeto (id_usuario, titulo, status, id_orcamento) VALUES (?, ?, 'Agendamento Pendente', ?)";
        $pdo->prepare($sql_projeto)->execute([$orc['id_usuario'], $orc['titulo_sugerido'], $id_orcamento]);

        // 2. Muda o orçamento para aprovado final
        $pdo->prepare("UPDATE orcamento SET status = 'Aprovado' WHERE id_orcamento = ?")->execute([$id_orcamento]);

        header("Location: ../pages/agendamentos-cliente.php?sucesso=aceito");
        exit();
    } catch (PDOException $e) {
        exit();
    }
}
