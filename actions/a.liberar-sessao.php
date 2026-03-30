<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php'; // Adicionamos o motor de e-mail

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['projeto_id'])) {
    $projeto_id = $_POST['projeto_id'];

    // Recebe os novos valores (que vão valer para a PRÓXIMA sessão)
    $valor_post = $_POST['valor_sessao'] ?? '0';
    $tempo_post = $_POST['estimativa_tempo'] ?? '';

    try {
        // 1. Descobrir qual é o orçamento vinculado a esse projeto
        $stmt_proj = $pdo->prepare("SELECT id_orcamento FROM projeto WHERE id_projeto = ?");
        $stmt_proj->execute([$projeto_id]);
        $id_orcamento = $stmt_proj->fetchColumn();

        if ($id_orcamento) {
            // 2. Guarda o valor ANTIGO
            $stmt_orc = $pdo->prepare("SELECT valor_sessao, estimativa_tempo FROM orcamento WHERE id_orcamento = ?");
            $stmt_orc->execute([$id_orcamento]);
            $orc_atual = $stmt_orc->fetch(PDO::FETCH_ASSOC);

            $valor_antigo = $orc_atual['valor_sessao'] ?? 0;
            $tempo_antigo = $orc_atual['estimativa_tempo'] ?? '';

            // 3. Conclui a sessão de hoje guardando o valor histórico dela
            $sql_sessao = "UPDATE sessao SET status = 'Concluído', valor_sessao = ?, estimativa_tempo = ? WHERE id_projeto = ? AND status = 'Agendado'";
            $pdo->prepare($sql_sessao)->execute([$valor_antigo, $tempo_antigo, $projeto_id]);

            // 4. Atualiza a regra geral (orçamento) para as PRÓXIMAS sessões
            $valor_formatado = str_replace('.', '', $valor_post);
            $valor_formatado = str_replace(',', '.', $valor_formatado);
            $valor_float = (float)$valor_formatado;

            $sql_update_orc = "UPDATE orcamento SET valor_sessao = ?, estimativa_tempo = ? WHERE id_orcamento = ?";
            $pdo->prepare($sql_update_orc)->execute([$valor_float, $tempo_post, $id_orcamento]);
        }

        // 5. Volta o projeto para a Ação Requerida do cliente para ele escolher nova data
        $sql_projeto = "UPDATE projeto SET status = 'Agendamento Pendente', motivo_reagendamento = NULL WHERE id_projeto = ?";
        $pdo->prepare($sql_projeto)->execute([$projeto_id]);

        // ================= NOTIFICAÇÃO (PARA O CLIENTE) =================
        // Busca os dados do cliente e do projeto atualizados para avisá-lo
        $stmt_cli = $pdo->prepare("
            SELECT u.nome, u.email, p.titulo, o.local_corpo, o.tamanho_aproximado, o.qtd_sessoes, o.estimativa_tempo, o.valor_sessao
            FROM projeto p 
            JOIN orcamento o ON p.id_orcamento = o.id_orcamento 
            JOIN usuario u ON o.id_usuario = u.id_usuario 
            WHERE p.id_projeto = ?
        ");
        $stmt_cli->execute([$projeto_id]);
        $cliente = $stmt_cli->fetch();

        if ($cliente) {
            // Mandamos direto para a tela de escolher a data do projeto em questão
            $link = "https://" . $_SERVER['HTTP_HOST'] . "/pages/agendar-sessao-cliente.php?projeto_id=" . $projeto_id;

            $valor_br = !empty($cliente['valor_sessao']) ? "R$ " . number_format((float)$cliente['valor_sessao'], 2, ',', '.') : 'A definir';
            $estimativa_tempo = !empty($cliente['estimativa_tempo']) ? $cliente['estimativa_tempo'] : 'A definir';
            $qtd_sessoes = !empty($cliente['qtd_sessoes']) ? $cliente['qtd_sessoes'] : 'A definir';

            $msg = "
<div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
    <div style='text-align: center; margin-bottom: 30px;'><h1 style='color: #ffffff; margin: 0; letter-spacing: 2px;'>BIG HEAD TATTOO</h1></div>
    <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
        <h2 style='color: #ffffff; margin-top: 0; font-size: 20px;'>PRÓXIMA SESSÃO LIBERADA! ⏳</h2>
        <p style='font-size: 16px; color: #cccccc;'>Olá, <strong>{$cliente['nome']}</strong>!</p>
        <p style='font-size: 15px; color: #cccccc;'>O artista acabou de concluir sua sessão anterior e liberou no sistema o agendamento para a próxima etapa da sua tatuagem.</p>
        
        <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
            <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 15px 0;'>Detalhes da Próxima Sessão</h3>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Projeto em Andamento:</strong> {$cliente['titulo']}</p>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Local e Tamanho:</strong> {$cliente['local_corpo']} ({$cliente['tamanho_aproximado']})</p>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Sessões Estimadas Totais:</strong> {$qtd_sessoes}</p>
            <hr style='border-top: 1px solid #333; margin: 15px 0;'>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Duração para a Próxima:</strong> {$estimativa_tempo}</p>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Valor da Próxima Sessão:</strong> {$valor_br}</p>
            <p style='margin: 0; color: #cccccc;'><strong style='color: #ffffff;'>Status:</strong> Aguardando você escolher a data</p>
        </div>

        <p style='font-size: 14px; color: #aaa; text-align: center; margin-top: 30px;'>
            Respeite o tempo de cicatrização recomendado pelo artista antes de agendar a próxima data.
        </p>

        <div style='text-align: center; margin-top: 30px; margin-bottom: 10px;'>
            <a href='{$link}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>AGENDAR PRÓXIMA SESSÃO</a>
        </div>
    </div>
</div>";
            dispararEmail($cliente['email'], $cliente['nome'], "Sua Próxima Sessão foi Liberada | Big Head Tattoo", $msg);
        }
        // ================================================================

        header("Location: ../pages/agenda.php?sucesso=sessao_liberada");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agenda.php?erro=bd");
        exit();
    }
}
