<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_portfolio = $_POST['id_portfolio'];
    $id_estilo = $_POST['id_estilo'];
    $titulo = $_POST['titulo'];
    $tempo = $_POST['tempo_execucao'];
    $sessoes = $_POST['qtd_sessoes'];
    $local = $_POST['local_corpo'];
    $descricao = $_POST['descricao'];

    try {
        // 1. Verificar se uma nova imagem foi enviada
        if (!empty($_FILES['imagem']['name'])) {
            // Buscar imagem antiga para deletar o arquivo físico
            $stmt_old = $pdo->prepare("SELECT imagem FROM portfolio WHERE id_portfolio = ?");
            $stmt_old->execute([$id_portfolio]);
            $old_img = $stmt_old->fetchColumn();

            if ($old_img && file_exists("../imagens/portfolio/" . $old_img)) {
                unlink("../imagens/portfolio/" . $old_img);
            }

            // Upload da nova imagem
            $arquivo = $_FILES['imagem'];
            $nome_imagem = time() . "_" . $arquivo['name'];
            move_uploaded_file($arquivo['tmp_name'], "../imagens/portfolio/" . $nome_imagem);

            // Update com imagem nova
            $sql = "UPDATE portfolio SET id_estilo = ?, titulo = ?, imagem = ?, tempo_execucao = ?, qtd_sessoes = ?, local_corpo = ?, descricao = ? WHERE id_portfolio = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_estilo, $titulo, $nome_imagem, $tempo, $sessoes, $local, $descricao, $id_portfolio]);
        } else {
            // Update sem mexer na imagem
            $sql = "UPDATE portfolio SET id_estilo = ?, titulo = ?, tempo_execucao = ?, qtd_sessoes = ?, local_corpo = ?, descricao = ? WHERE id_portfolio = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_estilo, $titulo, $tempo, $sessoes, $local, $descricao, $id_portfolio]);
        }

        header("Location: ../pages/portfolio-artista.php?sucesso=editado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/portfolio-artista.php?erro=db_edit");
        exit();
    }
} else {
    header("Location: ../pages/portfolio-artista.php");
    exit();
}
