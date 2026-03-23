<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') exit();

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $sessao_id = $_POST['sessao_id'];

    // Pega a página de origem (se não existir, o padrão será a agenda)
    $pagina_origem = isset($_POST['origem']) && !empty($_POST['origem']) ? $_POST['origem'] : 'agenda.php';

    // Deixa no mesmo padrão das outras telas
    $motivo = "Reagendado pelo Artista: " . trim($_POST['motivo']);

    try {
        $pdo->prepare("UPDATE sessao SET status = 'Cancelado', motivo_cancelamento = ? WHERE id_sessao = ?")->execute([$motivo, $sessao_id]);

        $stmt_projeto = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
        $stmt_projeto->execute([$sessao_id]);
        $id_projeto = $stmt_projeto->fetchColumn();

        $pdo->prepare("UPDATE projeto SET status = 'Agendamento Pendente', motivo_reagendamento = ? WHERE id_projeto = ?")->execute([$motivo, $id_projeto]);

        // Redireciona de volta para a página que enviou a requisição
        header("Location: ../pages/" . $pagina_origem . "?sucesso=reagendado");
        exit();
    } catch (PDOException $e) {
        // Se der erro, também volta para a página certa
        header("Location: ../pages/" . $pagina_origem . "?erro=bd");
        exit();
    }
}
