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
    $motivo_recusa = trim($_POST['motivo_recusa']);
    $origem = $_POST['origem'] ?? 'dashboard-artista.php';

    try {
        $sql = "UPDATE orcamento SET status = 'Recusado', motivo_recusa = ? WHERE id_orcamento = ?";
        $pdo->prepare($sql)->execute([$motivo_recusa, $id_orcamento]);

        // ================= NOTIFICAÇÃO DUPLA POR E-MAIL =================
        $stmt_cli = $pdo->prepare("
            SELECT u.nome, u.email, o.titulo_sugerido, o.local_corpo, o.tamanho_aproximado, o.qtd_sessoes, o.estimativa_tempo, o.valor_sessao 
            FROM orcamento o 
            JOIN usuario u ON o.id_usuario = u.id_usuario 
            WHERE o.id_orcamento = ?
        ");
        $stmt_cli->execute([$id_orcamento]);
        $cliente = $stmt_cli->fetch();

        $stmt_art = $pdo->query("SELECT email, nome FROM usuario WHERE perfil = 'artista' LIMIT 1");
        $artista = $stmt_art->fetch();

        if ($cliente && $artista) {
            $link = "https://" . $_SERVER['HTTP_HOST'] . "/pages/dashboard-cliente.php";
            $link_artista = "https://" . $_SERVER['HTTP_HOST'] . "/pages/dashboard-artista.php";
            $motivo_formatado = nl2br(htmlspecialchars($motivo_recusa));

            // Formatação das variáveis do projeto
            $titulo = !empty($cliente['titulo_sugerido']) ? $cliente['titulo_sugerido'] : 'Solicitação de Orçamento';
            $valor_br = !empty($cliente['valor_sessao']) ? "R$ " . number_format((float)$cliente['valor_sessao'], 2, ',', '.') : 'A definir';
            $estimativa_tempo = !empty($cliente['estimativa_tempo']) ? $cliente['estimativa_tempo'] : 'A definir';
            $qtd_sessoes = !empty($cliente['qtd_sessoes']) ? $cliente['qtd_sessoes'] : 'A definir';

            // Detalhes comuns do Espelho do Projeto
            $detalhes_comuns = "
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Projeto/Ideia:</strong> {$titulo}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Local do Corpo:</strong> {$cliente['local_corpo']}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Tamanho:</strong> {$cliente['tamanho_aproximado']}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Duração por Sessão:</strong> {$estimativa_tempo}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Sessões Estimadas:</strong> {$qtd_sessoes}</p>
                <p style='margin: 0; color: #cccccc;'><strong style='color: #ffffff;'>Valor por Sessão:</strong> {$valor_br}</p>
            ";

            // Bloco do Artista (com o nome do cliente)
            $bloco_detalhes_art = "
            <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
                <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Detalhes da Solicitação</h3>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Cliente:</strong> {$cliente['nome']}</p>
                {$detalhes_comuns}
            </div>";

            // Bloco do Cliente (sem o nome do cliente)
            $bloco_detalhes_cli = "
            <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
                <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Detalhes da Solicitação</h3>
                {$detalhes_comuns}
            </div>";

            // 1. E-MAIL PARA O CLIENTE
            $msg_cli = "
            <div style='font-family: Arial, Helvetica, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #ffffff; margin: 0; letter-spacing: 2px;'>BIG HEAD TATTOO</h1>
                </div>
                <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                    <h2 style='color: #ffffff; margin-top: 0; font-size: 20px;'>ATUALIZAÇÃO DO ORÇAMENTO</h2>
                    <p style='font-size: 16px; color: #cccccc;'>Olá, <strong>{$cliente['nome']}</strong>!</p>
                    <p style='font-size: 15px; color: #cccccc;'>O artista analisou com carinho a sua ideia, mas informou que não poderá seguir com o projeto no momento.</p>
                    
                    {$bloco_detalhes_cli}
                    
                    <div style='background-color: #1a1d20; padding: 15px; border-left: 4px solid #dc3545; margin: 25px 0;'>
                        <p style='margin: 0 0 5px 0; color: #aaa; font-size: 14px;'><strong style='color: #fff;'>Mensagem do Estúdio:</strong></p>
                        <p style='margin: 0; color: #ddd; line-height: 1.5;'><em>\"{$motivo_formatado}\"</em></p>
                    </div>
                    
                    <p style='font-size: 13px; color: #777; text-align: center; margin-top: 30px;'>
                        Agradecemos imensamente o interesse em nosso trabalho e esperamos tatuar você no futuro!
                    </p>

                    <div style='text-align: center; margin-top: 30px; margin-bottom: 10px;'>
                        <a href='{$link}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>ACESSAR MEU PAINEL</a>
                    </div>
                </div>
            </div>";
            dispararEmail($cliente['email'], $cliente['nome'], "Atualização do Orçamento | Big Head Tattoo", $msg_cli);

            // 2. E-MAIL PARA O ARTISTA (COMPROVANTE)
            $msg_art = "
            <div style='font-family: Arial, Helvetica, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #ffffff; margin: 0; letter-spacing: 2px;'>BIG HEAD TATTOO</h1>
                </div>
                <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
                    <h2 style='color: #ffffff; margin-top: 0; font-size: 20px;'>OPERAÇÃO CONCLUÍDA</h2>
                    <p style='font-size: 16px; color: #cccccc;'>Olá, <strong>{$artista['nome']}</strong>!</p>
                    <p style='font-size: 15px; color: #cccccc;'>O cancelamento da proposta ou a recusa do orçamento do cliente <strong>{$cliente['nome']}</strong> foi registrado no sistema com sucesso.</p>
                    
                    {$bloco_detalhes_art}
                    
                    <div style='background-color: #1a1d20; padding: 15px; border-left: 4px solid #dc3545; margin: 25px 0;'>
                        <p style='margin: 0 0 5px 0; color: #aaa; font-size: 14px;'><strong style='color: #fff;'>Seu motivo informado:</strong></p>
                        <p style='margin: 0; color: #ddd; line-height: 1.5;'><em>\"{$motivo_formatado}\"</em></p>
                    </div>

                    <div style='text-align: center; margin-top: 30px; margin-bottom: 10px;'>
                        <a href='{$link_artista}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>IR PARA O PAINEL</a>
                    </div>
                </div>
            </div>";
            dispararEmail($artista['email'], $artista['nome'], "Confirmação: Orçamento/Proposta Recusada | Big Head Tattoo", $msg_art);
        }
        // ==========================================================

        $separador = (strpos($origem, '?') !== false) ? '&' : '?';
        header("Location: ../pages/" . $origem . $separador . "sucesso=recusado");
        exit();
    } catch (PDOException $e) {
        $separador = (strpos($origem, '?') !== false) ? '&' : '?';
        header("Location: ../pages/" . $origem . $separador . "erro=bd");
        exit();
    }
} else {
    header("Location: ../pages/dashboard-artista.php");
    exit();
}
