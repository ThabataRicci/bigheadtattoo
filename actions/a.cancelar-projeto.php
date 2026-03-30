<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php';

// redireciona se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    exit();
}

$perfil = $_SESSION['usuario_perfil'];
$pagina_padrao = ($perfil == 'artista') ? 'dashboard-artista.php' : 'agendamentos-cliente.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // recebe qual ID foi enviado (Sessão ou Projeto)
    $id_sessao = !empty($_POST['sessao_id']) ? $_POST['sessao_id'] : null;
    $id_projeto = !empty($_POST['projeto_id']) ? $_POST['projeto_id'] : null;

    $pagina_origem = isset($_POST['origem']) && !empty($_POST['origem']) ? $_POST['origem'] : $pagina_padrao;

    if (!$id_sessao && !$id_projeto) {
        header("Location: ../pages/" . $pagina_origem);
        exit();
    }

    $quem_cancelou = ($perfil === 'artista') ? 'Artista' : 'Cliente';
    $motivo_puro = trim($_POST['motivo']);
    $motivo = "Cancelado pelo " . $quem_cancelou . ": " . $motivo_puro;

    try {
        if ($id_sessao) {
            $stmt = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
            $stmt->execute([$id_sessao]);
            $id_projeto_encontrado = $stmt->fetchColumn();

            if ($id_projeto_encontrado) {
                $id_projeto = $id_projeto_encontrado;
                $sql_sessao = "UPDATE sessao SET status = 'Cancelado', motivo_cancelamento = ? WHERE id_sessao = ?";
                $pdo->prepare($sql_sessao)->execute([$motivo, $id_sessao]);
            }
        }

        if ($id_projeto) {
            $sql_projeto = "UPDATE projeto SET status = 'Cancelado', motivo_reagendamento = NULL WHERE id_projeto = ?";
            $pdo->prepare($sql_projeto)->execute([$id_projeto]);

            // ================= NOTIFICAÇÃO DUPLA (COMPROVANTE + AVISO) =================
            // Puxando todos os detalhes do projeto e orçamento (sem a data da sessão)
            $stmt_cli = $pdo->prepare("
                SELECT u.nome, u.email, p.titulo, o.local_corpo, o.tamanho_aproximado, o.qtd_sessoes, o.estimativa_tempo, o.valor_sessao 
                FROM projeto p 
                JOIN orcamento o ON p.id_orcamento = o.id_orcamento 
                JOIN usuario u ON o.id_usuario = u.id_usuario 
                WHERE p.id_projeto = ?
            ");
            $stmt_cli->execute([$id_projeto]);
            $cliente = $stmt_cli->fetch();

            $stmt_art = $pdo->query("SELECT email, nome FROM usuario WHERE perfil = 'artista' LIMIT 1");
            $artista = $stmt_art->fetch();

            if ($cliente && $artista) {
                $motivo_seguro = nl2br(htmlspecialchars($motivo_puro));
                $valor_br = number_format((float)$cliente['valor_sessao'], 2, ',', '.');
                $link_painel = "https://" . $_SERVER['HTTP_HOST'] . "/pages/login.php";

                // INFORMAÇÕES COMUNS (SEM A DATA E HORA)
                $detalhes_comuns = "
                    <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Projeto:</strong> {$cliente['titulo']}</p>
                    <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Local do Corpo:</strong> {$cliente['local_corpo']}</p>
                    <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Tamanho:</strong> {$cliente['tamanho_aproximado']}</p>
                    <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Duração por Sessão:</strong> {$cliente['estimativa_tempo']}</p>
                    <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Sessões Estimadas:</strong> {$cliente['qtd_sessoes']}</p>
                    <p style='margin: 0; color: #cccccc;'><strong style='color: #ffffff;'>Valor por Sessão:</strong> R$ {$valor_br}</p>
                ";

                // BLOCO DO ARTISTA (COM O NOME DO CLIENTE)
                $bloco_detalhes_art = "
                <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
                    <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Detalhes do Projeto</h3>
                    <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Cliente:</strong> {$cliente['nome']}</p>
                    {$detalhes_comuns}
                </div>";

                // BLOCO DO CLIENTE (SEM O NOME)
                $bloco_detalhes_cli = "
                <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
                    <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Detalhes do Projeto</h3>
                    {$detalhes_comuns}
                </div>";

                // Se o CLIENTE cancelou
                if ($perfil === 'cliente') {
                    // 1. E-mail para o Artista (Aviso)
                    $msg_art = "
                    <div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                        <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                            <h2 style='color: #ffffff; margin-top: 0;'>PROJETO CANCELADO</h2>
                            <p style='color: #ccc;'>Olá, <strong>{$artista['nome']}</strong>! O cliente <strong>{$cliente['nome']}</strong> cancelou o projeto definitivamente no sistema.</p>
                            {$bloco_detalhes_art}
                            <div style='background-color: #1a1d20; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>
                                <p style='color: #ccc; margin: 0;'><strong style='color: #fff;'>Motivo do Cliente:</strong><br><em>\"{$motivo_seguro}\"</em></p>
                            </div>
                            <div style='text-align: center; margin-top: 30px;'><a href='{$link_painel}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>VER MEU PAINEL</a></div>
                        </div>
                    </div>";
                    dispararEmail($artista['email'], $artista['nome'], "Projeto Cancelado pelo Cliente", $msg_art);

                    // 2. E-mail para o Cliente (Comprovante)
                    $msg_cli = "
                    <div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                        <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                            <h2 style='color: #ffffff; margin-top: 0;'>CANCELAMENTO CONFIRMADO</h2>
                            <p style='color: #ccc;'>Olá, <strong>{$cliente['nome']}</strong>. Confirmamos que o seu projeto foi cancelado com sucesso em nosso sistema, conforme sua solicitação.</p>
                            {$bloco_detalhes_cli}
                            <p style='color: #ccc; text-align: center; margin-top: 20px;'>Agradecemos o seu contato e esperamos ter a oportunidade de tatuar você no futuro!</p>
                        </div>
                    </div>";
                    dispararEmail($cliente['email'], $cliente['nome'], "Confirmação de Cancelamento | Big Head Tattoo", $msg_cli);

                    // Se o ARTISTA cancelou
                } else {
                    // 1. E-mail para o Cliente (Aviso)
                    $msg_cli = "
                    <div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                        <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                            <h2 style='color: #ffffff; margin-top: 0;'>PROJETO CANCELADO</h2>
                            <p style='color: #ccc;'>Olá, <strong>{$cliente['nome']}</strong>. Infelizmente o estúdio precisou cancelar o seu projeto de tatuagem.</p>
                            {$bloco_detalhes_cli}
                            <div style='background-color: #1a1d20; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>
                                <p style='color: #ccc; margin: 0;'><strong style='color: #fff;'>Mensagem do Estúdio:</strong><br><em>\"{$motivo_seguro}\"</em></p>
                            </div>
                        </div>
                    </div>";
                    dispararEmail($cliente['email'], $cliente['nome'], "Aviso: Projeto Cancelado", $msg_cli);

                    // 2. E-mail para o Artista (Comprovante)
                    $msg_art = "
                    <div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                        <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                            <h2 style='color: #ffffff; margin-top: 0;'>CANCELAMENTO REALIZADO</h2>
                            <p style='color: #ccc;'>Olá, <strong>{$artista['nome']}</strong>. O cancelamento do projeto do cliente <strong>{$cliente['nome']}</strong> foi registrado com sucesso.</p>
                            {$bloco_detalhes_art}
                        </div>
                    </div>";
                    dispararEmail($artista['email'], $artista['nome'], "Confirmação de Cancelamento", $msg_art);
                }
            }
            // =======================================================================
        }

        header("Location: ../pages/" . $pagina_origem . "?sucesso=cancelado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/" . $pagina_origem . "?erro=bd");
        exit();
    }
} else {
    header("Location: ../pages/" . $pagina_padrao);
    exit();
}
