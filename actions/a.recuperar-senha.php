<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // 1. Verificar se esse e-mail realmente existe no banco
    $stmt = $pdo->prepare("SELECT id_usuario, nome FROM usuario WHERE email = ? AND status != 'Excluido'");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // 2. Gerar um token e a data de expiração (1 hora a partir de agora)
        $token = bin2hex(random_bytes(32)); // Gera um código de 64 caracteres
        $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 3. Salvar no banco
        $stmt_token = $pdo->prepare("UPDATE usuario SET token_recuperacao = ?, expiracao_token = ? WHERE id_usuario = ?");
        $stmt_token->execute([$token, $expiracao, $usuario['id_usuario']]);

        // 4. Montar o link de recuperação 
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

        $caminho_base = $protocolo . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI'], 2);
        $link = $caminho_base . "/pages/redefinir-senha.php?token=" . $token;

        // 5. Enviar o e-mail
        $assunto = "Recuperação de Senha - Big Head Tattoo";
        $mensagem = "Olá, " . $usuario['nome'] . "!\n\n";
        $mensagem .= "Você solicitou a recuperação de senha. Clique no link abaixo para criar uma nova senha:\n";
        $mensagem .= $link . "\n\n";
        $mensagem .= "Este link é válido por 1 hora.\n";
        $mensagem .= "Se você não solicitou isso, ignore este e-mail.";

        $dominio = $_SERVER['HTTP_HOST'];
        $headers = "From: sistema@" . $dominio . "\r\n";
        $headers .= "Reply-To: sistema@" . $dominio . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($email, $assunto, $mensagem, $headers);

        header("Location: ../pages/login.php?sucesso=recuperacao_enviada");
        exit();
    } else {
        header("Location: ../pages/recuperar-senha.php?erro=email_nao_encontrado");
        exit();
    }
} else {
    header("Location: ../pages/recuperar-senha.php");
    exit();
}
