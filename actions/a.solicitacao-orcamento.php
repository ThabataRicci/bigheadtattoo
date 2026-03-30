<?php
session_start();
require_once '../includes/conexao.php';
require_once '../includes/enviar_email.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_SESSION['usuario_id'];
    $local_corpo = $_POST['local_corpo'];
    $tamanho_aproximado = $_POST['tamanho_aproximado'];
    $descricao_ideia = $_POST['descricao_ideia'];
    $nome_foto = null;

    if (isset($_FILES['referencia_ideia']) && $_FILES['referencia_ideia']['error'] === 0) {
        $extensao = pathinfo($_FILES['referencia_ideia']['name'], PATHINFO_EXTENSION);
        $nome_foto = "orcamento_" . $id_usuario . "_" . time() . "." . $extensao;

        if (!is_dir("../imagens/orcamentos/")) {
            mkdir("../imagens/orcamentos/", 0777, true);
        }
        move_uploaded_file($_FILES['referencia_ideia']['tmp_name'], "../imagens/orcamentos/" . $nome_foto);
    }

    try {
        $sql = "INSERT INTO orcamento (id_usuario, local_corpo, tamanho_aproximado, descricao_ideia, referencia_ideia, data_envio) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $pdo->prepare($sql)->execute([$id_usuario, $local_corpo, $tamanho_aproximado, $descricao_ideia, $nome_foto]);

        // ================= NOTIFICAÇÃO POR E-MAIL =================
        $stmt_cli = $pdo->prepare("SELECT nome FROM usuario WHERE id_usuario = ?");
        $stmt_cli->execute([$id_usuario]);
        $cliente = $stmt_cli->fetch();

        $stmt_art = $pdo->query("SELECT email, nome FROM usuario WHERE perfil = 'artista' LIMIT 1");
        $artista = $stmt_art->fetch();

        if ($cliente && $artista) {
            $link = "https://" . $_SERVER['HTTP_HOST'] . "/pages/dashboard-artista.php";
            $ideia_formatada = nl2br(htmlspecialchars($descricao_ideia));

            $msg = "
<div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
    <div style='text-align: center; margin-bottom: 30px;'>
        <h1 style='color: #ffffff; margin: 0; letter-spacing: 2px;'>BIG HEAD TATTOO</h1>
    </div>
    <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
        <h2 style='color: #ffffff; margin-top: 0; font-size: 20px;'>NOVO PEDIDO NA FILA</h2>
        <p style='font-size: 16px; color: #cccccc;'>Olá, <strong>{$artista['nome']}</strong>!</p>
        <p style='font-size: 15px; color: #cccccc;'>Uma nova solicitação de orçamento acaba de chegar no sistema e aguarda sua avaliação.</p>
        
        <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
            <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 15px 0;'>Detalhes da Solicitação</h3>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Cliente:</strong> {$cliente['nome']}</p>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Localização:</strong> {$local_corpo}</p>
            <p style='margin: 0 0 10px 0; color: #cccccc;'><strong style='color: #ffffff;'>Tamanho:</strong> {$tamanho_aproximado}</p>
            <p style='margin: 0; color: #cccccc;'><strong style='color: #ffffff;'>Ideia principal:</strong><br>{$ideia_formatada}</p>
        </div>

        <div style='text-align: center; margin-top: 40px; margin-bottom: 10px;'>
            <a href='{$link}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>ANALISAR ORÇAMENTO</a>
        </div>
    </div>
</div>";
            dispararEmail($artista['email'], $artista['nome'], "Novo Orçamento | Big Head Tattoo", $msg);
        }
        // ==========================================================

        header("Location: ../pages/solicitar-orcamento.php?sucesso=1");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/solicitar-orcamento.php?erro=1");
        exit();
    }
}
