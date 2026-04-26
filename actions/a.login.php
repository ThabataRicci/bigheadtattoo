<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $redirect = $_POST['redirect'] ?? '';

    $sql = "SELECT id_usuario, nome, senha, perfil, status, data_nascimento FROM usuario WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {

        // verificar se o cliente está bloqueado
        if (isset($usuario['status']) && $usuario['status'] === 'Bloqueado') {

            // verifica a idade atual do cliente
            if (!empty($usuario['data_nascimento'])) {
                $nasc_cliente = new DateTime($usuario['data_nascimento']);
                $hoje = new DateTime('today');
                $idade_atual = $nasc_cliente->diff($hoje)->y;

                if ($idade_atual >= 18) {
                    // fez 18 anos
                    $stmt_desbloqueio = $pdo->prepare("UPDATE usuario SET status = 'Ativo' WHERE id_usuario = ?");
                    $stmt_desbloqueio->execute([$usuario['id_usuario']]);
                    $usuario['status'] = 'Ativo';
                } else {
                    // menor de idade
                    header("Location: ../pages/login.php?erro=bloqueado");
                    exit();
                }
            } else {
                header("Location: ../pages/login.php?erro=bloqueado");
                exit();
            }
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
