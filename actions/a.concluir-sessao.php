<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $sessao_id = $_POST['sessao_id'];

    try {
        $stmt_s = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
        $stmt_s->execute([$sessao_id]);
        $projeto_id = $stmt_s->fetchColumn();

        if ($projeto_id) {
            $stmt_proj = $pdo->prepare("SELECT id_orcamento FROM projeto WHERE id_projeto = ?");
            $stmt_proj->execute([$projeto_id]);
            $id_orcamento = $stmt_proj->fetchColumn();

            $valor_antigo = 0;
            $tempo_antigo = '';
            if ($id_orcamento) {
                $stmt_orc = $pdo->prepare("SELECT valor_sessao, estimativa_tempo FROM orcamento WHERE id_orcamento = ?");
                $stmt_orc->execute([$id_orcamento]);
                $orc_atual = $stmt_orc->fetch(PDO::FETCH_ASSOC);
                $valor_antigo = $orc_atual['valor_sessao'] ?? 0;
                $tempo_antigo = $orc_atual['estimativa_tempo'] ?? '';
            }

            $sql_sessao = "UPDATE sessao SET status = 'Concluído', valor_sessao = ?, estimativa_tempo = ? WHERE id_sessao = ?";
            $pdo->prepare($sql_sessao)->execute([$valor_antigo, $tempo_antigo, $sessao_id]);

            $sql_projeto = "UPDATE projeto SET status = 'Finalizado' WHERE id_projeto = ?";
            $pdo->prepare($sql_projeto)->execute([$projeto_id]);

            // ================= NOTIFICAÇÃO: CUIDADOS PÓS-TATUAGEM =================
            $stmt_cli = $pdo->prepare("SELECT u.nome, u.email, p.titulo FROM projeto p JOIN orcamento o ON p.id_orcamento = o.id_orcamento JOIN usuario u ON o.id_usuario = u.id_usuario WHERE p.id_projeto = ?");
            $stmt_cli->execute([$projeto_id]);
            $cliente = $stmt_cli->fetch();

            if ($cliente) {
                $msg = "
<div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
    <div style='text-align: center; margin-bottom: 30px;'><h1 style='color: #ffffff; margin: 0; letter-spacing: 2px;'>BIG HEAD TATTOO</h1></div>
    <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
        <h2 style='color: #ffffff; margin-top: 0; font-size: 20px;'>TATUAGEM FINALIZADA! 🤘</h2>
        <p style='font-size: 16px; color: #cccccc;'>Muito obrigado pela confiança, <strong>{$cliente['nome']}</strong>!</p>
        <p style='font-size: 15px; color: #cccccc;'>O projeto <strong>{$cliente['titulo']}</strong> foi finalizado no sistema.</p>
        
        <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
            <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Cuidados Essenciais (15 Dias)</h3>
            <ul style='color: #cccccc; margin: 0; padding-left: 20px; line-height: 1.6;'>
                <li>Lave suavemente com sabonete neutro (sem esfregar).</li>
                <li>Passe uma camada de pomada cicatrizante 3x ao dia.</li>
                <li><strong>Não puxe as casquinhas</strong> nem coce de jeito nenhum.</li>
                <li>Evite sol direto, praia, piscina ou sauna.</li>
                <li>Evite carne de porco, frutos do mar e alimentos muito gordurosos.</li>
            </ul>
        </div>
        <p style='color: #777; font-size: 13px; text-align: center;'>Qualquer dúvida durante a cicatrização, entre em contato direto pelo nosso WhatsApp!</p>
    </div>
</div>";
                dispararEmail($cliente['email'], $cliente['nome'], "Sua Tatuagem + Cuidados Cicatrização | Big Head Tattoo", $msg);
            }
            // ======================================================================
        }

        header("Location: ../pages/agenda.php?sucesso=concluido");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agenda.php?erro=bd");
        exit();
    }
}
