<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['usuario_id'])) {
    $id = $_SESSION['usuario_id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $tel_limpo = preg_replace('/[^0-9]/', '', $_POST['telefone']);
    $biografia = $_POST['biografia'];
    $estilos = $_POST['estilos'] ?? [];

    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    $tipo_sucesso = "1";

    try {
        // atualizaçao de nome, email, telefone e bio
        $sql = "UPDATE usuario SET nome = ?, email = ?, telefone = ?, biografia = ? WHERE id_usuario = ?";
        $pdo->prepare($sql)->execute([$nome, $email, $tel_limpo, $biografia, $id]);

        // lógica da senha (se a nova senha for preenchida)
        if (!empty($nova_senha)) {
            $stmt_senha = $pdo->prepare("SELECT senha FROM usuario WHERE id_usuario = ?");
            $stmt_senha->execute([$id]);
            $user_db = $stmt_senha->fetch();

            // a) valida senha atual pra ver se ta correta
            if (!password_verify($senha_atual, $user_db['senha'])) {
                header("Location: ../pages/configuracoes-artista.php?erro=senha_atual");
                exit();
            }
            // b) valida se a nova é igual a atual
            if (password_verify($nova_senha, $user_db['senha'])) {
                header("Location: ../pages/configuracoes-artista.php?erro=senha_igual");
                exit();
            }
            // c) valida confirmacao
            if ($nova_senha !== $confirmar_senha) {
                header("Location: ../pages/configuracoes-artista.php?erro=confirmacao");
                exit();
            }
            // d) valida regras de caracteres
            if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/', $nova_senha)) {
                header("Location: ../pages/configuracoes-artista.php?erro=senha_fraca");
                exit();
            }

            // salva nova senha
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuario SET senha = ? WHERE id_usuario = ?")->execute([$hash, $id]);
            $tipo_sucesso = "senha";
        }

        // atualizacao de estilos
        $pdo->prepare("DELETE FROM artista_estilo WHERE id_artista = ?")->execute([$id]);
        foreach ($estilos as $id_estilo) {
            $pdo->prepare("INSERT INTO artista_estilo (id_artista, id_estilo) VALUES (?, ?)")->execute([$id, $id_estilo]);
        }

        // atualizacao de fotos
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
            $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $nome_foto = "perfil_" . $id . "." . $ext;
            if (!is_dir("../imagens/perfil/")) mkdir("../imagens/perfil/", 0777, true);

            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], "../imagens/perfil/" . $nome_foto)) {
                $pdo->prepare("UPDATE usuario SET foto_perfil = ? WHERE id_usuario = ?")->execute([$nome_foto, $id]);
            }
        }

        $_SESSION['usuario_nome'] = $nome; // atualiza nome do artista na dash
        header("Location: ../pages/configuracoes-artista.php?sucesso=" . $tipo_sucesso);
        exit();
    } catch (PDOException $e) {
        die("Erro ao salvar: " . $e->getMessage());
    }
}
