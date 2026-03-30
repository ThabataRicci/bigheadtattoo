<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') exit();

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $sessao_id = $_POST['sessao_id'];
    $pagina_origem = isset($_POST['origem']) && !empty($_POST['origem']) ? $_POST['origem'] : 'agenda.php';
    $motivo_puro = trim($_POST['motivo']);
    $motivo = "Reagendado pelo Artista: " . $motivo_puro;

    try {
        $pdo->prepare("UPDATE sessao SET status = 'Cancelado', motivo_cancelamento = ? WHERE id_sessao = ?")->execute([$motivo, $sessao_id]);

        $stmt_projeto = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
        $stmt_projeto->execute([$sessao_id]);
        $id_projeto = $stmt_projeto->fetchColumn();

        $pdo->prepare("UPDATE projeto SET status = 'Agendamento Pendente', motivo_reagendamento = ? WHERE id_projeto = ?")->execute([$motivo, $id_projeto]);

        // ================= NOTIFICAÇÃO DUPLA (COMPROVANTE + AVISO) =================
        $stmt_cli = $pdo->prepare("
            SELECT u.nome, u.email, p.titulo, o.local_corpo, o.tamanho_aproximado, o.qtd_sessoes, o.estimativa_tempo, o.valor_sessao, s.data_hora 
            FROM projeto p 
            JOIN orcamento o ON p.id_orcamento = o.id_orcamento 
            JOIN usuario u ON o.id_usuario = u.id_usuario 
            LEFT JOIN sessao s ON p.id_projeto = s.id_projeto AND s.status = 'Cancelado' AND s.motivo_cancelamento LIKE ? 
            WHERE p.id_projeto = ?
            ORDER BY s.id_sessao DESC LIMIT 1
        ");
        $stmt_cli->execute(["%" . $motivo . "%", $id_projeto]);
        $cliente = $stmt_cli->fetch();

        $stmt_art = $pdo->query("SELECT email, nome FROM usuario WHERE perfil = 'artista' LIMIT 1");
        $artista = $stmt_art->fetch();

        if ($cliente && $artista) {
            $motivo_seguro = nl2br(htmlspecialchars($motivo_puro));
            $valor_br = number_format((float)$cliente['valor_sessao'], 2, ',', '.');

            $link_cliente = "https://" . $_SERVER['HTTP_HOST'] . "/pages/agendamentos-cliente.php";

            $linha_data = "";
            if (!empty($cliente['data_hora'])) {
                $data_formatada = date('d/m/Y', strtotime($cliente['data_hora']));
                $hora_formatada = date('H:i', strtotime($cliente['data_hora']));
                $linha_data = "<p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Data e Hora:</strong> {$data_formatada} às {$hora_formatada}</p>";
            }

            $detalhes_comuns = "
                {$linha_data}
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Projeto:</strong> {$cliente['titulo']}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Local do Corpo:</strong> {$cliente['local_corpo']}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Tamanho:</strong> {$cliente['tamanho_aproximado']}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Duração por Sessão:</strong> {$cliente['estimativa_tempo']}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Sessões Estimadas:</strong> {$cliente['qtd_sessoes']}</p>
                <p style='margin: 0; color: #cccccc;'><strong style='color: #ffffff;'>Valor por Sessão:</strong> R$ {$valor_br}</p>
            ";

            $bloco_detalhes_art = "
            <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
                <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Detalhes do Projeto</h3>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Cliente:</strong> {$cliente['nome']}</p>
                {$detalhes_comuns}
            </div>";

            $bloco_detalhes_cli = "
            <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
                <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Detalhes do Projeto</h3>
                {$detalhes_comuns}
            </div>";

            // Aviso pro Cliente
            $msg_cli = "
            <div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                    <h2 style='color: #ffffff; margin-top: 0;'>PRECISAMOS REAGENDAR SUA SESSÃO</h2>
                    <p style='color: #ccc;'>Olá, <strong>{$cliente['nome']}</strong>! Ocorreu um imprevisto e o estúdio precisou desmarcar a sua sessão atual. Mas não se preocupe, seu projeto continua ativo!</p>
                    {$bloco_detalhes_cli}
                    <div style='background-color: #1a1d20; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>
                        <p style='color: #ccc; margin: 0;'><strong style='color: #fff;'>Mensagem do Estúdio:</strong><br><em>\"{$motivo_seguro}\"</em></p>
                    </div>
                    <p style='color: #ccc; text-align: center; margin-top: 20px;'>Acesse seu painel agora mesmo para escolher uma nova data que fique boa para você.</p>
                    <div style='text-align: center; margin-top: 30px;'><a href='{$link_cliente}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>ESCOLHER NOVA DATA</a></div>
                </div>
            </div>";
            dispararEmail($cliente['email'], $cliente['nome'], "Aviso: Reagendamento Necessário | Big Head Tattoo", $msg_cli);

            // Comprovante pro Artista
            $msg_art = "
            <div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                    <h2 style='color: #ffffff; margin-top: 0;'>SESSÃO CANCELADA | REAGENDAMENTO</h2>
                    <p style='color: #ccc;'>Olá, <strong>{$artista['nome']}</strong>! A sessão abaixo foi cancelada e o cliente já foi notificado para escolher uma nova data.</p>
                    {$bloco_detalhes_art}
                </div>
            </div>";
            dispararEmail($artista['email'], $artista['nome'], "Confirmação de Reagendamento", $msg_art);
        }
        // ================================================================

        header("Location: ../pages/" . $pagina_origem . "?sucesso=reagendado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/" . $pagina_origem . "?erro=bd");
        exit();
    }
}
