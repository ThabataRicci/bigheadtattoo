<?php
session_start();
require_once '../includes/conexao.php';

// apenas o artista pode aprovar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {

    $id_orcamento = $_POST['orcamento_id'];
    $estimativa_tempo = $_POST['estimativa_tempo'];
    $qtd_sessoes = $_POST['qtd_sessoes'];
    $titulo_projeto = trim($_POST['titulo_projeto']);

    // NOVOS CAMPOS
    $valor_sessao = $_POST['valor_sessao'];

    // A mágica de saber pra qual tela voltar (se veio da Dashboard ou da Agenda):
    $origem = $_POST['origem'] ?? 'dashboard-artista.php';

    try {
        // Atualiza o orçamento enviando a proposta para o cliente avaliar
        // Atenção: O Projeto NÃO é criado aqui mais, só quando o cliente aceitar!
        $sql = "UPDATE orcamento 
                SET status = 'Aguardando Aceite', estimativa_tempo = ?, qtd_sessoes = ?, valor_sessao = ?, titulo_sugerido = ? 
                WHERE id_orcamento = ?";

        $pdo->prepare($sql)->execute([$estimativa_tempo, $qtd_sessoes, $valor_sessao, $titulo_projeto, $id_orcamento]);

        // Retorna para a tela de onde o artista clicou (origem)
        header("Location: ../pages/" . $origem . "?sucesso=proposta_enviada");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/" . $origem . "?erro=bd");
        exit();
    }
} else {
    header("Location: ../pages/dashboard-artista.php");
    exit();
}
