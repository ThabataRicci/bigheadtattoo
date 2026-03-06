<?php
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $telefone_formatado = $_POST['telefone'];
    $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone_formatado);
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirmar = $_POST['confirmar-senha'];

    // verificar senhas
    if ($senha !== $confirmar) {
        header("Location: ../pages/cadastro.php?erro=senha");
        exit();
    }

    // validar força da senha (mínimo 8 caracteres, 1 maiúscula, 1 número)
    if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/', $senha)) {
        header("Location: ../pages/cadastro.php?erro=senha_fraca");
        exit();
    }

    // verificar se ja existe o numero de telefone cadastrado
    $sql_busca_tel = "SELECT id_usuario FROM usuario WHERE telefone = ?";
    $stmt_busca_tel = $pdo->prepare($sql_busca_tel);
    $stmt_busca_tel->execute([$telefone_limpo]);

    if ($stmt_busca_tel->fetch()) {
        header("Location: ../pages/cadastro.php?erro=telefone");
        exit();
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO usuario (nome, telefone, email, senha, perfil) VALUES (?, ?, ?, ?, 'cliente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $telefone_limpo, $email, $senha_hash]);

        header("Location: ../pages/login.php?sucesso=1");
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            header("Location: ../pages/cadastro.php?erro=email");
            exit();
        }
        die("Erro ao cadastrar: " . $e->getMessage());
    }
}
