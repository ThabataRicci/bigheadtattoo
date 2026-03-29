<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_artista = $_SESSION['usuario_id'];
    $id_estilo = $_POST['id_estilo'];
    $titulo = $_POST['titulo'];
    $tempo = $_POST['tempo_execucao'];
    $sessoes = $_POST['qtd_sessoes'];
    $local = $_POST['local_corpo'];
    $descricao = $_POST['descricao'];

    // --- LOGICA DE UPLOAD DA IMAGEM ---
    $arquivo = $_FILES['imagem'];
    $nome_arquivo = time() . "_" . $arquivo['name'];
    $caminho_destino = "../imagens/portfolio/" . $nome_arquivo;

    if (move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
        try {
            $sql = "INSERT INTO portfolio (id_artista, id_estilo, titulo, imagem, tempo_execucao, qtd_sessoes, local_corpo, descricao, data_publicacao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_artista, $id_estilo, $titulo, $nome_arquivo, $tempo, $sessoes, $local, $descricao]);

            header("Location: ../pages/portfolio-artista.php?sucesso=1");
        } catch (PDOException $e) {
            header("Location: ../pages/portfolio-artista.php?erro=db");
        }
    } else {
        header("Location: ../pages/portfolio-artista.php?erro=upload");
    }
}
