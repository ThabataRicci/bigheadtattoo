<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $sql = "SELECT id_usuario, nome, senha, perfil FROM usuario WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {

        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_perfil'] = $usuario['perfil'];

        if ($usuario['perfil'] == 'artista') {
            header("Location: ../pages/dashboard-artista.php");
        } else {
            header("Location: ../pages/dashboard-cliente.php");
        }
        exit();
    } else {
        header("Location: ../pages/login.php?erro=credenciais");
        exit();
    }
}
