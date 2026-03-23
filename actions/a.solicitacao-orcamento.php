<?php
session_start();
require_once '../includes/conexao.php';

// verifica qual o usuario logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_SESSION['usuario_id'];
    $local_corpo = $_POST['local_corpo'];
    $tamanho_aproximado = $_POST['tamanho_aproximado'];
    $descricao_ideia = $_POST['descricao_ideia'];
    $nome_foto = null;

    // processamento da foto de referencia anexada
    if (isset($_FILES['referencia_ideia']) && $_FILES['referencia_ideia']['error'] === 0) {
        $extensao = pathinfo($_FILES['referencia_ideia']['name'], PATHINFO_EXTENSION);
        $nome_foto = "orcamento_" . $id_usuario . "_" . time() . "." . $extensao;

        // cria pasta pra salvar imagens anexadas
        if (!is_dir("../imagens/orcamentos/")) {
            mkdir("../imagens/orcamentos/", 0777, true);
        }

        move_uploaded_file($_FILES['referencia_ideia']['tmp_name'], "../imagens/orcamentos/" . $nome_foto);
    }

    try {
        $sql = "INSERT INTO orcamento (id_usuario, local_corpo, tamanho_aproximado, descricao_ideia, referencia_ideia, data_envio) 
                VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_usuario, $local_corpo, $tamanho_aproximado, $descricao_ideia, $nome_foto]);

        header("Location: ../pages/solicitar-orcamento.php?sucesso=1");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/solicitar-orcamento.php?erro=1");
        exit();
    }
} else {
    header("Location: ../pages/solicitar-orcamento.php");
    exit();
}
