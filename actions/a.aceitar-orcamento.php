<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {
    $id_orcamento = $_POST['orcamento_id'];
    $id_usuario = $_SESSION['usuario_id'];

    try {
        $stmt = $pdo->prepare("SELECT titulo_sugerido FROM orcamento WHERE id_orcamento = ? AND id_usuario = ?");
        $stmt->execute([$id_orcamento, $id_usuario]);
        $titulo = $stmt->fetchColumn() ?: 'Projeto de Tatuagem';

        $pdo->prepare("UPDATE orcamento SET status = 'Aprovado' WHERE id_orcamento = ?")->execute([$id_orcamento]);

        $sql_proj = "INSERT INTO projeto (id_usuario, id_orcamento, titulo, status) VALUES (?, ?, ?, 'Agendamento Pendente')";
        $pdo->prepare($sql_proj)->execute([$id_usuario, $id_orcamento, $titulo]);

        $stmt_new = $pdo->prepare("SELECT id_projeto FROM projeto WHERE id_orcamento = ? ORDER BY id_projeto DESC LIMIT 1");
        $stmt_new->execute([$id_orcamento]);
        $id_projeto = $stmt_new->fetchColumn();

        // ================= NOTIFICAÇÃO POR E-MAIL =================
        // Busca os dados completos de quem aceitou e do orçamento
        $stmt_cli = $pdo->prepare("
            SELECT u.nome, o.local_corpo, o.tamanho_aproximado, o.qtd_sessoes, o.estimativa_tempo, o.valor_sessao, o.titulo_sugerido 
            FROM orcamento o JOIN usuario u ON o.id_usuario = u.id_usuario 
            WHERE o.id_orcamento = ?
        ");
        $stmt_cli->execute([$id_orcamento]);
        $dados = $stmt_cli->fetch();

        $stmt_art = $pdo->query("SELECT email, nome FROM usuario WHERE perfil = 'artista' LIMIT 1");
        $artista = $stmt_art->fetch();

        if ($dados && $artista) {
            $link = "https://" . $_SERVER['HTTP_HOST'] . "/pages/dashboard-artista.php";
            $valor_br = number_format((float)$dados['valor_sessao'], 2, ',', '.');

            $msg = "
<div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
    <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
        <h2 style='color: #ffffff; margin-top: 0;'>PROPOSTA ACEITA! 🎉</h2>
        <p style='color: #cccccc;'>Olá, <strong>{$artista['nome']}</strong>! Temos uma ótima notícia: o cliente aprovou a sua proposta.</p>
        
        <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
            <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Resumo do Projeto Fechado</h3>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Cliente:</strong> {$dados['nome']}</p>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Projeto:</strong> {$dados['titulo_sugerido']}</p>
       <p style='margin: 0 0 5px 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Local:</strong> {$dados['local_corpo']}
</p>

<p style='margin: 0 0 10px 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Tamanho:</strong> {$dados['tamanho_aproximado']}
</p>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Sessões Fechadas:</strong> {$dados['qtd_sessoes']}</p>
            <p style='margin: 0; color: #cccccc;'><strong style='color: #ffffff;'>Valor por Sessão:</strong> R$ {$valor_br} ({$dados['estimativa_tempo']} por sessão)</p>
        </div>

        <div style='text-align: center; margin-top: 35px;'><a href='{$link}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>VER PROJETOS ATIVOS</a></div>
    </div>
</div>";
            dispararEmail($artista['email'], $artista['nome'], "Projeto Fechado! | Big Head Tattoo", $msg);
        }
        // ==========================================================

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
