<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {
    $id_orcamento = $_POST['orcamento_id'];
    $id_usuario = $_SESSION['usuario_id'];

    try {
        // 1. Busca os detalhes do orçamento
        $stmt = $pdo->prepare("SELECT titulo_sugerido FROM orcamento WHERE id_orcamento = ? AND id_usuario = ?");
        $stmt->execute([$id_orcamento, $id_usuario]);
        $titulo = $stmt->fetchColumn() ?: 'Projeto de Tatuagem';

        // 2. Marca o orçamento como Aprovado
        $pdo->prepare("UPDATE orcamento SET status = 'Aprovado' WHERE id_orcamento = ?")->execute([$id_orcamento]);

        // 3. Cria o Projeto na tabela 'projeto'
        $sql_proj = "INSERT INTO projeto (id_usuario, id_orcamento, titulo, status) VALUES (?, ?, ?, 'Agendamento Pendente')";
        $pdo->prepare($sql_proj)->execute([$id_usuario, $id_orcamento, $titulo]);

        // 4. Busca o ID DO PROJETO recém-criado forçando a leitura no banco
        $stmt_new = $pdo->prepare("SELECT id_projeto FROM projeto WHERE id_orcamento = ? ORDER BY id_projeto DESC LIMIT 1");
        $stmt_new->execute([$id_orcamento]);
        $id_projeto = $stmt_new->fetchColumn();

        // 5. Manda para a agenda COM O ID do projeto na URL
        if ($id_projeto) {
            header("Location: ../pages/agendar-sessao-cliente.php?projeto_id=" . $id_projeto);
        } else {
            header("Location: ../pages/agendamentos-cliente.php?erro=bd");
        }
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agendamentos-cliente.php?erro=bd");
        exit();
    }
}
