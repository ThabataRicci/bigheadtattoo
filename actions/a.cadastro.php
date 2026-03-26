<?php
session_start();
require_once '../includes/conexao.php'; // conexao banco de dados

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $telefone_formatado = $_POST['telefone'];
    $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone_formatado);
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $confirmar = $_POST['confirmar-senha'];
    $redirect = $_POST['redirect'] ?? '';

    // verificar se as senhas coincidem
    if ($senha !== $confirmar) {
        header("Location: ../pages/cadastro.php?erro=senha&redirect=" . urlencode($redirect));
        exit();
    }

    // validar força da senha (mínimo 8 caracteres, 1 maiúscula, 1 número)
    if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/', $senha)) {
        header("Location: ../pages/cadastro.php?erro=senha_fraca&redirect=" . urlencode($redirect));
        exit();
    }

    // verificar se já existe o número de telefone cadastrado
    $sql_busca_tel = "SELECT id_usuario FROM usuario WHERE telefone = ?";
    $stmt_busca_tel = $pdo->prepare($sql_busca_tel);
    $stmt_busca_tel->execute([$telefone_limpo]);

    if ($stmt_busca_tel->fetch()) {
        header("Location: ../pages/cadastro.php?erro=telefone&redirect=" . urlencode($redirect));
        exit();
    }

    // gerar o hash seguro da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        // inserir no banco de dados
        $sql = "INSERT INTO usuario (nome, telefone, email, senha, perfil) VALUES (?, ?, ?, ?, 'cliente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $telefone_limpo, $email, $senha_hash]);

        // define as variáveis de sessão p usuario já entrar logado
        $id_novo_usuario = $pdo->lastInsertId();
        $_SESSION['usuario_id'] = $id_novo_usuario;
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_perfil'] = 'cliente';
        $_SESSION['loggedin'] = true;

        // se o usuario veio da página de orçamento, volta para lá. se nao vai para o dashboard
        if ($redirect === 'solicitar-orcamento.php') {
            header("Location: ../pages/solicitar-orcamento.php");
        } else {
            header("Location: ../pages/dashboard-cliente.php");
        }
        exit();
    } catch (PDOException $e) {
        // código 23000 indica que o email ja existe
        if ($e->getCode() == 23000) {
            header("Location: ../pages/cadastro.php?erro=email&redirect=" . urlencode($redirect));
            exit();
        }
        die("Erro ao cadastrar: " . $e->getMessage());
    }
} else {
    header("Location: ../pages/cadastro.php");
    exit();
}
