<?php
session_start();
require_once '../includes/conexao.php';

// aepnas o artista pode aprovar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {

    $id_orcamento = $_POST['orcamento_id'];
    $estimativa_tempo = $_POST['estimativa_tempo'];
    $qtd_sessoes = $_POST['qtd_sessoes'];
    $titulo_projeto = trim($_POST['titulo_projeto']);

    try {
        // 1. descobrir a que cliente (id_usuario) eh o orçamento
        $stmt_user = $pdo->prepare("SELECT id_usuario FROM orcamento WHERE id_orcamento = ?");
        $stmt_user->execute([$id_orcamento]);
        $id_cliente = $stmt_user->fetchColumn();

        if ($id_cliente) {
            // 2. atualiza o orçamento com as estimativas e muda o status
            $sql_orcamento = "UPDATE orcamento 
                              SET status = 'Aprovado', estimativa_tempo = ?, qtd_sessoes = ? 
                              WHERE id_orcamento = ?";
            $pdo->prepare($sql_orcamento)->execute([$estimativa_tempo, $qtd_sessoes, $id_orcamento]);

            // o projeto entra automaticamente como 'Agendamento Pendente' para aparecer na Ação Requerida do cliente
            $sql_projeto = "INSERT INTO projeto (id_usuario, titulo, status, id_orcamento) 
                            VALUES (?, ?, 'Agendamento Pendente', ?)";
            $pdo->prepare($sql_projeto)->execute([$id_cliente, $titulo_projeto, $id_orcamento]);
        }

        // retorna para a dashboard com sucesso
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
