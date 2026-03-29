<?php

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function dispararEmail($para_email, $para_nome, $assunto, $mensagem_html)
{
    $mail = new PHPMailer(true);

    try {
        // configuracoes do servidor (gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        $mail->Username   = 'notificacoes.bigheadtattoo@gmail.com';
        $mail->Password   = 'luldvxcwkeomaldy';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // remetente e destinatário
        $mail->setFrom('notificacoes.bigheadtattoo@gmail.com', 'Big Head Tattoo');
        $mail->addAddress($para_email, $para_nome);

        // Conteúdo do E-mail
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $mensagem_html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
        return false;
    }
}
