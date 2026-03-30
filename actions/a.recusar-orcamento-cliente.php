<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {
    $id_orcamento = $_POST['orcamento_id'];
    $tipo_recusa = $_POST['tipo_recusa'];

    try {
        // Busca TODOS os dados básicos para o e-mail antes de alterar o banco
        $stmt_dados = $pdo->prepare("
            SELECT u.nome AS nome_cliente, o.titulo_sugerido, o.local_corpo, o.tamanho_aproximado, o.qtd_sessoes, o.estimativa_tempo, o.valor_sessao 
            FROM orcamento o 
            JOIN usuario u ON o.id_usuario = u.id_usuario 
            WHERE o.id_orcamento = ?
        ");
        $stmt_dados->execute([$id_orcamento]);
        $dados_orcamento = $stmt_dados->fetch();

        // Variáveis Inteligentes para o Card
        if ($tipo_recusa == 'preco') {
            $sql = "UPDATE orcamento SET status = 'Negociacao', tentativas_negociacao = tentativas_negociacao + 1 WHERE id_orcamento = ?";
            $pdo->prepare($sql)->execute([$id_orcamento]);

            $titulo_email = "CLIENTE QUER NEGOCIAR";
            $acao_cliente = "Solicitou nova análise de preço";
            $motivo_detalhe = "O cliente achou o valor da proposta alto e deseja renegociar para fechar o projeto.";
            $cor_borda = "#0dcaf0"; // Azul para negociação
        } else {
            $motivo = $_POST['motivo_cancelamento_cliente'];
            $sql = "UPDATE orcamento SET status = 'Cancelado pelo Cliente', motivo_cancelamento_cliente = ? WHERE id_orcamento = ?";
            $pdo->prepare($sql)->execute([$motivo, $id_orcamento]);

            $titulo_email = "PROPOSTA RECUSADA";
            $acao_cliente = "Recusou e encerrou o fluxo";
            $motivo_detalhe = "<em>\"" . nl2br(htmlspecialchars($motivo)) . "\"</em>";
            $cor_borda = "#dc3545"; // Vermelho para recusa/cancelamento
        }

        // ================= NOTIFICAÇÃO POR E-MAIL =================
        $stmt_art = $pdo->query("SELECT email, nome FROM usuario WHERE perfil = 'artista' LIMIT 1");
        $artista = $stmt_art->fetch();

        if ($artista && $dados_orcamento) {
            $link = "https://" . $_SERVER['HTTP_HOST'] . "/pages/dashboard-artista.php";
            $nome_cliente = $dados_orcamento['nome_cliente'];
            $titulo_proj = $dados_orcamento['titulo_sugerido'] ?: 'Projeto sem título';
            $valor_br = !empty($dados_orcamento['valor_sessao']) ? "R$ " . number_format((float)$dados_orcamento['valor_sessao'], 2, ',', '.') : 'A definir';

            // Bloco de detalhes do projeto
            $bloco_detalhes = "
            <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
                <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Detalhes do Projeto</h3>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Cliente:</strong> {$nome_cliente}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Projeto:</strong> {$titulo_proj}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Local e Tamanho:</strong> {$dados_orcamento['local_corpo']} ({$dados_orcamento['tamanho_aproximado']})</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Sessões Estimadas:</strong> {$dados_orcamento['qtd_sessoes']}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Duração por Sessão:</strong> {$dados_orcamento['estimativa_tempo']}</p>
                <p style='margin: 0; color: #cccccc;'><strong style='color: #ffffff;'>Valor da Proposta:</strong> {$valor_br}</p>
            </div>";

            $msg = "
<div style='font-family: Arial, Helvetica, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
    <div style='text-align: center; margin-bottom: 30px;'>
        <h1 style='color: #ffffff; margin: 0; letter-spacing: 2px;'>BIG HEAD TATTOO</h1>
    </div>
    <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
        <h2 style='color: #ffffff; margin-top: 0; font-size: 20px;'>{$titulo_email}</h2>
        <p style='font-size: 16px; color: #cccccc;'>Olá, <strong>{$artista['nome']}</strong>!</p>
        <p style='font-size: 15px; color: #cccccc;'>Tivemos uma atualização no status da proposta enviada.</p>
        
        {$bloco_detalhes}

        <div style='background-color: #1a1d20; padding: 15px 20px; border-radius: 5px; border-left: 4px solid {$cor_borda}; margin: 25px 0;'>
            <p style='margin: 0 0 5px 0; color: #cccccc;'><strong style='color: #ffffff;'>Ação tomada:</strong> {$acao_cliente}</p>
            <p style='margin: 0; color: #cccccc;'><strong style='color: #ffffff;'>Detalhes:</strong><br>{$motivo_detalhe}</p>
        </div>

        <div style='text-align: center; margin-top: 40px; margin-bottom: 10px;'>
            <a href='{$link}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>VER ORÇAMENTOS</a>
        </div>
    </div>
</div>";
            dispararEmail($artista['email'], $artista['nome'], "Atualização de Proposta | Big Head Tattoo", $msg);
        }
        // ==========================================================

        header("Location: ../pages/agendamentos-cliente.php?sucesso=recusado");
        exit();
    } catch (PDOException $e) {
        exit();
    }
}
