<?php
session_start();
require_once '../includes/conexao.php';

require_once '../includes/enviar_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    try {
        // 1. Verificar se esse e-mail realmente existe no banco
        $stmt = $pdo->prepare("SELECT id_usuario, nome FROM usuario WHERE email = ? AND status != 'Excluido'");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // 2. Gerar um token e a data de expiração (1 hora a partir do momento q gerar)
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // 3. Salvar no banco
            $stmt_token = $pdo->prepare("UPDATE usuario SET token_recuperacao = ?, expiracao_token = ? WHERE id_usuario = ?");
            $stmt_token->execute([$token, $expiracao, $usuario['id_usuario']]);

            // 4. Montar o link de recuperação 
            $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $caminho_base = $protocolo . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI'], 2);
            $link = $caminho_base . "/pages/redefinir-senha.php?token=" . $token;

            // 5. ENVIAR O E-MAIL (
            $assunto = "Recuperação de Senha - Big Head Tattoo";

            // criamos a mensagem em HTML para ficar com um botão bonito no e-mail
            $mensagem = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2>Olá, {$usuario['nome']}!</h2>
                    <p>Você solicitou a recuperação de senha no sistema Big Head Tattoo.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <br>
                    <a href='{$link}' style='padding: 12px 24px; background-color: #00bbff; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>CRIAR NOVA SENHA</a>
                    <br><br><br>
                    <p style='font-size: 12px; color: #777;'>Este link é válido por 1 hora. Se você não solicitou isso, ignore este e-mail.</p>
                </div>
            ";

            if (dispararEmail($email, $usuario['nome'], $assunto, $mensagem)) {

                header("Location: ../pages/login.php?sucesso=recuperacao_enviada");
                exit();
            } else {

                die("ERRO: Não foi possível enviar o e-mail. Verifique as configurações do PHPMailer no arquivo enviar_email.php.");
            }
        } else {
            header("Location: ../pages/recuperar-senha.php?erro=email_nao_encontrado");
            exit();
        }
    } catch (PDOException $e) {
        die("ERRO NO BANCO DE DADOS: " . $e->getMessage());
    } catch (Exception $e) {
        die("ERRO GERAL: " . $e->getMessage());
    }
} else {
    header("Location: ../pages/recuperar-senha.php");
    exit();
}
