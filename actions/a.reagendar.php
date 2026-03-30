<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $id_sessao = $_POST['sessao_id'];
    $perfil = $_SESSION['usuario_perfil'];

    $quem_reagendou = ($perfil === 'artista') ? 'Artista' : 'Cliente';
    $motivo_puro = trim($_POST['motivo']);
    $motivo = "Reagendado pelo " . $quem_reagendou . ": " . $motivo_puro;

    try {
        $stmt = $pdo->prepare("SELECT id_projeto, valor_sessao, estimativa_tempo FROM sessao WHERE id_sessao = ?");
        $stmt->execute([$id_sessao]);
        $sessao_antiga = $stmt->fetch(PDO::FETCH_ASSOC);

        $id_projeto = $sessao_antiga['id_projeto'] ?? 0;

        if ($id_projeto) {
            $stmt_proj = $pdo->prepare("SELECT id_orcamento FROM projeto WHERE id_projeto = ?");
            $stmt_proj->execute([$id_projeto]);
            $id_orcamento = $stmt_proj->fetchColumn();

            $valor_base = 0;
            $tempo_base = '';
            if ($id_orcamento) {
                $stmt_orc = $pdo->prepare("SELECT valor_sessao, estimativa_tempo FROM orcamento WHERE id_orcamento = ?");
                $stmt_orc->execute([$id_orcamento]);
                $orc_atual = $stmt_orc->fetch(PDO::FETCH_ASSOC);
                $valor_base = $orc_atual['valor_sessao'] ?? 0;
                $tempo_base = $orc_atual['estimativa_tempo'] ?? '';
            }

            $valor_historico = $sessao_antiga['valor_sessao'] ?? $valor_base;
            $tempo_historico = $sessao_antiga['estimativa_tempo'] ?? $tempo_base;

            $sql_sessao = "UPDATE sessao SET status = 'Cancelado', motivo_cancelamento = ?, valor_sessao = ?, estimativa_tempo = ? WHERE id_sessao = ?";
            $pdo->prepare($sql_sessao)->execute([$motivo, $valor_historico, $tempo_historico, $id_sessao]);

            $sql_projeto = "UPDATE projeto SET status = 'Agendamento Pendente', motivo_reagendamento = ? WHERE id_projeto = ?";
            $pdo->prepare($sql_projeto)->execute([$motivo, $id_projeto]);

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
                $link_artista = "https://" . $_SERVER['HTTP_HOST'] . "/pages/dashboard-artista.php";

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

                // Se o CLIENTE reagendou
                if ($perfil === 'cliente') {
                    // Aviso pro Artista
                    $msg_art = "
                    <div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                        <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                            <h2 style='color: #ffffff; margin-top: 0;'>SESSÃO CANCELADA | REAGENDAMENTO</h2>
                            <p style='color: #ccc;'>Olá, <strong>{$artista['nome']}</strong>! O cliente cancelou o horário atual e o projeto voltou para a fila de agendamento pendente.</p>
                            {$bloco_detalhes_art}
                            <div style='background-color: #1a1d20; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>
                                <p style='color: #ccc; margin: 0;'><strong style='color: #fff;'>Motivo do Cliente:</strong><br><em>\"{$motivo_seguro}\"</em></p>
                            </div>
                            <div style='text-align: center; margin-top: 30px;'><a href='{$link_artista}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>VER PAINEL</a></div>
                        </div>
                    </div>";
                    dispararEmail($artista['email'], $artista['nome'], "Aviso: Cliente quer reagendar | Big Head Tattoo", $msg_art);

                    // Comprovante pro Cliente
                    $msg_cli = "
                    <div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                        <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                            <h2 style='color: #ffffff; margin-top: 0;'>REAGENDAMENTO SOLICITADO</h2>
                            <p style='color: #ccc;'>Olá, <strong>{$cliente['nome']}</strong>! Confirmamos que sua solicitação de reagendamento foi registrada e o horário atual foi liberado no sistema.</p>
                            {$bloco_detalhes_cli}
                            <p style='color: #ccc; text-align: center; margin-top: 20px;'>Acesse o painel para escolher uma nova data.</p>
                            <div style='text-align: center; margin-top: 30px;'><a href='{$link_cliente}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>ESCOLHER NOVA DATA</a></div>
                        </div>
                    </div>";
                    dispararEmail($cliente['email'], $cliente['nome'], "Sessão Cancelada | Reagendamento | Big Head Tattoo", $msg_cli);

                    // Se o ARTISTA reagendou (Ex: Pela dashboard dele)
                } else {
                    // Aviso pro Cliente
                    $msg_cli = "
                    <div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                        <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                            <h2 style='color: #ffffff; margin-top: 0;'>PRECISAMOS REAGENDAR SUA SESSÃO</h2>
                            <p style='color: #ccc;'>Olá, <strong>{$cliente['nome']}</strong>! Ocorreu um imprevisto e o artista precisou cancelar a sua sessão atual. Mas não se preocupe, seu projeto continua ativo!</p>
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
                            <h2 style='color: #ffffff; margin-top: 0;'>SESSÃO CANCELADA NO SISTEMA</h2>
                            <p style='color: #ccc;'>Olá, <strong>{$artista['nome']}</strong>! A sessão abaixo foi cancelada e o cliente já foi notificado para escolher uma nova data.</p>
                            {$bloco_detalhes_art}
                        </div>
                    </div>";
                    dispararEmail($artista['email'], $artista['nome'], "Confirmação de Reagendamento", $msg_art);
                }
            }
            // =======================================================
        }

        if ($perfil == 'cliente') {
            header("Location: ../pages/agendar-sessao-cliente.php?projeto_id=" . $id_projeto);
        } else {
            header("Location: ../pages/dashboard-artista.php?sucesso=reagendado");
        }
        exit();
    } catch (PDOException $e) {
        $pagina_erro = ($perfil == 'cliente') ? 'agendamentos-cliente.php' : 'dashboard-artista.php';
        header("Location: ../pages/" . $pagina_erro . "?erro=bd");
        exit();
    }
}
