<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $redirect = $_POST['redirect'] ?? '';

    // CORREÇÃO AQUI: Adicionado o "status" na busca do banco
    $sql = "SELECT id_usuario, nome, senha, perfil, status FROM usuario WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {

        // verificar se o cliente está bloqueado
        if (isset($usuario['status']) && $usuario['status'] === 'Bloqueado') {
            header("Location: ../pages/login.php?erro=bloqueado");
            exit();
        }

        // sucesso login
        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_perfil'] = $usuario['perfil'];
        $_SESSION['loggedin'] = true;

        // se veio da página de orçamento volta pra lá, se não vai pro dashboard normal
        if ($redirect === 'solicitar-orcamento.php') {
            header("Location: ../pages/solicitar-orcamento.php");
        } else {
            if ($usuario['perfil'] == 'artista') {
                header("Location: ../pages/dashboard-artista.php");
            } else {
                header("Location: ../pages/dashboard-cliente.php");
            }
        }
        exit();
    } else {
        // erro no login (senha ou email incorretos)
        // redireciona de volta para o login com o aviso de erro
        header("Location: ../pages/login.php?erro=credenciais&redirect=" . urlencode($redirect));
        exit();
    }
} else {
    header("Location: ../pages/login.php");
    exit();
}
