<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['projeto_id']) && !empty($_POST['data_sessao']) && !empty($_POST['hora_sessao'])) {
    $projeto_id = $_POST['projeto_id'];
    $data_sessao = $_POST['data_sessao'];
    $hora_sessao = $_POST['hora_sessao'];
    $data_hora = $data_sessao . ' ' . $hora_sessao . ':00';

    try {
        $stmt_trava = $pdo->prepare("SELECT id_sessao FROM sessao WHERE id_projeto = ? AND data_hora = ? AND status = 'Agendado'");
        $stmt_trava->execute([$projeto_id, $data_hora]);

        if ($stmt_trava->rowCount() > 0) {
            header("Location: ../pages/agendamentos-cliente.php?sucesso=agendado");
            exit();
        }

        $pdo->prepare("INSERT INTO sessao (id_projeto, data_hora, status) VALUES (?, ?, 'Agendado')")->execute([$projeto_id, $data_hora]);
        $pdo->prepare("UPDATE projeto SET status = 'Em Andamento', motivo_reagendamento = NULL WHERE id_projeto = ?")->execute([$projeto_id]);

        // ================= NOTIFICAÇÃO POR E-MAIL DUPLO =================
        // Busca todos os dados do projeto puxando do orçamento para o card completo
        $stmt_dados = $pdo->prepare("
    SELECT p.titulo, u.nome, u.email, o.local_corpo, o.tamanho_aproximado, o.qtd_sessoes, o.valor_sessao, o.estimativa_tempo
    FROM projeto p 
    JOIN orcamento o ON p.id_orcamento = o.id_orcamento 
    JOIN usuario u ON o.id_usuario = u.id_usuario 
    WHERE p.id_projeto = ?
");
        $stmt_dados->execute([$projeto_id]);
        $dados = $stmt_dados->fetch();

        $stmt_art = $pdo->query("SELECT email, nome FROM usuario WHERE perfil = 'artista' LIMIT 1");
        $artista = $stmt_art->fetch();

        if ($dados && $artista) {
            $data_formatada = date('d/m/Y', strtotime($data_sessao));
            $hora_formatada = date('H:i', strtotime($hora_sessao));
            $valor_br = number_format((float)$dados['valor_sessao'], 2, ',', '.');

            $link_cliente = "https://" . $_SERVER['HTTP_HOST'] . "/pages/agendamentos-cliente.php";
            $link_artista = "https://" . $_SERVER['HTTP_HOST'] . "/pages/agenda.php";

            // Bloco de Detalhes Reutilizável
            $bloco_detalhes = "
            <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
                <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; margin: 0 0 15px 0;'>Resumo do Agendamento e Projeto</h3>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Cliente:</strong> {$dados['nome']}</p>
                <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Data e Hora:</strong> {$data_formatada} às {$hora_formatada}</p>
                <hr style='border: 1px solid #333; margin: 15px 0;'>
            <p style='margin: 0 0 5px 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Projeto:</strong> {$dados['titulo']}
</p>

<p style='margin: 0 0 5px 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Local do Corpo:</strong> {$dados['local_corpo']}
</p>

<p style='margin: 0 0 5px 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Tamanho:</strong> {$dados['tamanho_aproximado']}
</p>

<p style='margin: 0 0 5px 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Duração por Sessão:</strong> {$dados['estimativa_tempo']}
</p>

<p style='margin: 0 0 5px 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Sessões Estimadas:</strong> {$dados['qtd_sessoes']}
</p>

<p style='margin: 0; color: #cccccc;'>
  <strong style='color: #ffffff;'>Valor por Sessão:</strong> R$ {$valor_br}
</p>
            </div>";

            // E-MAIL CLIENTE
            $msg_cli = "<div style='font-family: Arial, sans-serif; background-color: #000000; padding: 40px 20px;'><div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'><h2 style='color: #ffffff; margin-top: 0;'>SESSÃO CONFIRMADA! 📅</h2><p style='color: #cccccc;'>Olá, <strong>{$dados['nome']}</strong>! Seu horário foi reservado com sucesso.</p> {$bloco_detalhes} <p style='color: #aaa; text-align: center; font-size: 14px;'>Lembrete: Alimente-se bem e não consuma álcool nas 24h anteriores.</p><div style='text-align: center; margin-top: 30px;'><a href='{$link_cliente}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>MEUS AGENDAMENTOS</a></div></div></div>";
            dispararEmail($dados['email'], $dados['nome'], "Sessão Confirmada | Big Head Tattoo", $msg_cli);

            // E-MAIL ARTISTA
            $msg_art = "<div style='font-family: Arial, sans-serif; background-color: #000000; padding: 40px 20px;'><div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'><h2 style='color: #ffffff; margin-top: 0;'>NOVO AGENDAMENTO</h2><p style='color: #cccccc;'>Olá, <strong>{$artista['nome']}</strong>! O cliente confirmou uma data na agenda.</p> {$bloco_detalhes} <div style='text-align: center; margin-top: 30px;'><a href='{$link_artista}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>ABRIR AGENDA</a></div></div></div>";
            dispararEmail($artista['email'], $artista['nome'], "Nova Sessão Agendada | Big Head Tattoo", $msg_art);
        }
        // ==============================================================

        header("Location: ../pages/agendamentos-cliente.php?sucesso=agendado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agendamentos-cliente.php?erro=bd");
        exit();
    }
}
