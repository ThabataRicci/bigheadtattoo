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

    // --- A MÁGICA ACONTECE AQUI ---
    // Pega o valor do formulário (ex: "1.500,00")
    $valor_sessao_formatado = $_POST['valor_sessao'];
    // Tira os pontos de milhar (vira "1500,00")
    $valor_sessao_formatado = str_replace('.', '', $valor_sessao_formatado);
    // Troca a vírgula por ponto (vira "1500.00" - padrão que o Banco de Dados ama!)
    $valor_sessao_formatado = str_replace(',', '.', $valor_sessao_formatado);

    // A mágica de saber pra qual tela voltar (se veio da Dashboard ou da Agenda):
    $origem = $_POST['origem'] ?? 'dashboard-artista.php';

    try {
        // TRUQUE DE MESTRE: 
        // 'valor_sessao_anterior = valor_sessao' pega o valor que estava lá e guarda na gaveta nova.
        // 'valor_sessao = ?' pega o valor novo que o artista digitou e põe na gaveta principal.
        $sql = "UPDATE orcamento 
                SET status = 'Aguardando Aceite', 
                    estimativa_tempo = ?, 
                    qtd_sessoes = ?, 
                    titulo_sugerido = ?,
                    valor_sessao_anterior = valor_sessao, 
                    valor_sessao = ? 
                WHERE id_orcamento = ?";

        $pdo->prepare($sql)->execute([$estimativa_tempo, $qtd_sessoes, $titulo_projeto, $valor_sessao_formatado, $id_orcamento]);

        // Retorna para a tela de onde o artista clicou (origem)
        header("Location: ../pages/" . $origem . "?sucesso=proposta_enviada");
        exit();
    } catch (PDOException $e) {
        // Se der erro, mostra o real motivo na URL
        header("Location: ../pages/" . $origem . "?erro=bd&msg=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../pages/dashboard-artista.php");
    exit();
}
