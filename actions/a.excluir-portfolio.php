<?php
session_start();
require_once '../includes/conexao.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // 1. Primeiro buscamos o nome da imagem no banco para poder deletar o arquivo
        $stmt_busca = $pdo->prepare("SELECT imagem FROM portfolio WHERE id_portfolio = ?");
        $stmt_busca->execute([$id]);
        $trabalho = $stmt_busca->fetch();

        if ($trabalho) {
            $caminho_imagem = "../imagens/portfolio/" . $trabalho['imagem'];

            // 2. Deleta o arquivo físico da pasta imagens/portfolio/
            if (file_exists($caminho_imagem)) {
                unlink($caminho_imagem);
            }

            // 3. Deleta o registro no banco de dados
            $stmt_del = $pdo->prepare("DELETE FROM portfolio WHERE id_portfolio = ?");
            $stmt_del->execute([$id]);

            header("Location: ../pages/portfolio-artista.php?sucesso=excluido");
        }
    } catch (PDOException $e) {
        header("Location: ../pages/portfolio-artista.php?erro=exclusao");
    }
} else {
    header("Location: ../pages/portfolio-artista.php");
}
