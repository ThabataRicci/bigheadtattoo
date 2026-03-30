<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {
    $id_orcamento = $_POST['orcamento_id'];
    $estimativa_tempo = $_POST['estimativa_tempo'];
    $qtd_sessoes = $_POST['qtd_sessoes'];
    $titulo_projeto = trim($_POST['titulo_projeto']);

    $valor_sessao_formatado = $_POST['valor_sessao'];
    $valor_sessao_formatado = str_replace('.', '', $valor_sessao_formatado);
    $valor_sessao_formatado = str_replace(',', '.', $valor_sessao_formatado);

    $origem = $_POST['origem'] ?? 'dashboard-artista.php';

    try {
        $sql = "UPDATE orcamento 
                SET status = 'Aguardando Aceite', estimativa_tempo = ?, qtd_sessoes = ?, titulo_sugerido = ?, valor_sessao_anterior = valor_sessao, valor_sessao = ? 
                WHERE id_orcamento = ?";
        $pdo->prepare($sql)->execute([$estimativa_tempo, $qtd_sessoes, $titulo_projeto, $valor_sessao_formatado, $id_orcamento]);

        // ================= NOTIFICAÇÃO POR E-MAIL =================
        // Busca TODOS os detalhes do orçamento e do cliente
        $stmt_cli = $pdo->prepare("
            SELECT u.nome, u.email, o.local_corpo, o.tamanho_aproximado 
            FROM orcamento o JOIN usuario u ON o.id_usuario = u.id_usuario 
            WHERE o.id_orcamento = ?
        ");
        $stmt_cli->execute([$id_orcamento]);
        $cliente = $stmt_cli->fetch();

        if ($cliente) {
            $link = "https://" . $_SERVER['HTTP_HOST'] . "/pages/dashboard-cliente.php";
            $valor_br = number_format((float)$valor_sessao_formatado, 2, ',', '.');

            $msg = "
<div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
    <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
        <h2 style='color: #ffffff; margin-top: 0;'>SUA PROPOSTA CHEGOU!</h2>
        <p style='color: #cccccc;'>Olá, <strong>{$cliente['nome']}</strong>! O artista finalizou a análise da sua ideia e elaborou uma proposta exclusiva para você.</p>
        
        <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
            <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Detalhes da Proposta</h3>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Projeto:</strong> {$titulo_projeto}</p>
          <p style='margin: 0 0 5px 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Local:</strong> {$cliente['local_corpo']}
</p>

<p style='margin: 0 0 10px 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Tamanho:</strong> {$cliente['tamanho_aproximado']}
</p>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Sessões Estimadas:</strong> {$qtd_sessoes}</p>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Duração por Sessão:</strong> {$estimativa_tempo}</p>
            <p style='margin: 0; color: #cccccc;'><strong style='color: #ffffff;'>Valor por Sessão:</strong> R$ {$valor_br}</p>
        </div>
        
        <p style='color: #cccccc; text-align: center;'>Acesse seu painel para aprovar a proposta e liberar o agendamento.</p>
        <div style='text-align: center; margin-top: 35px;'><a href='{$link}' style='background-color: #ffffff; color: #000000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>ANALISAR E APROVAR</a></div>
    </div>
</div>";
            dispararEmail($cliente['email'], $cliente['nome'], "Proposta Recebida | Big Head Tattoo", $msg);
        }
        // ==========================================================
        header("Location: ../pages/" . $origem . "?sucesso=proposta_enviada");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/" . $origem . "?erro=bd");
        exit();
    }
} else {
    header("Location: ../pages/dashboard-artista.php");
    exit();
}
