<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['usuario_id'])) {
    $id = $_SESSION['usuario_id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $tel_limpo = preg_replace('/\D/', '', $_POST['telefone']);

    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    $tipo_sucesso = "1";

    try {
        // verificar se o novo email ja existe cadastrado no banco
        $stmt_email = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario != ?");
        $stmt_email->execute([$email, $id]);
        if ($stmt_email->fetch()) {
            header("Location: configuracoes-cliente.php?erro=email_duplicado");
            exit();
        }

        // atualizacao de nome, email e telefone
        $sql = "UPDATE usuario SET nome = ?, email = ?, telefone = ? WHERE id_usuario = ?";
        $pdo->prepare($sql)->execute([$nome, $email, $tel_limpo, $id]);

        // logica da senha (se a nova senha for preenchida)
        if (!empty($nova_senha)) {
            $stmt_senha = $pdo->prepare("SELECT senha FROM usuario WHERE id_usuario = ?");
            $stmt_senha->execute([$id]);
            $user_db = $stmt_senha->fetch();

            // a) valida senha atual pra ver se ta correta
            if (!password_verify($senha_atual, $user_db['senha'])) {
                header("Location: configuracoes-cliente.php?erro=senha_atual");
                exit();
            }
            // b) valida se a nova é igual a atual
            if (password_verify($nova_senha, $user_db['senha'])) {
                header("Location: configuracoes-cliente.php?erro=senha_igual");
                exit();
            }
            // c) valida confirmacao
            if ($nova_senha !== $confirmar_senha) {
                header("Location: configuracoes-cliente.php?erro=confirmacao");
                exit();
            }
            // d) valida regras de caracteres
            if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/', $nova_senha)) {
                header("Location: configuracoes-cliente.php?erro=senha_fraca");
                exit();
            }

            // salva nova senha
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuario SET senha = ? WHERE id_usuario = ?")->execute([$hash, $id]);
            $tipo_sucesso = "senha";
        }

        $_SESSION['usuario_nome'] = $nome;
        header("Location: configuracoes-cliente.php?sucesso=" . $tipo_sucesso);
        exit();
    } catch (PDOException $e) {
        die("Erro ao atualizar dados: " . $e->getMessage());
    }
} else {
    header("Location: login.php");
    exit();
}
