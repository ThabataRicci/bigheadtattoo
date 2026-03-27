<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $sessao_id = $_POST['sessao_id'];

    try {
        // Pega o id do projeto desta sessão
        $stmt_s = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
        $stmt_s->execute([$sessao_id]);
        $projeto_id = $stmt_s->fetchColumn();

        if ($projeto_id) {
            $stmt_proj = $pdo->prepare("SELECT id_orcamento FROM projeto WHERE id_projeto = ?");
            $stmt_proj->execute([$projeto_id]);
            $id_orcamento = $stmt_proj->fetchColumn();

            $valor_antigo = 0;
            $tempo_antigo = '';

            if ($id_orcamento) {
                $stmt_orc = $pdo->prepare("SELECT valor_sessao, estimativa_tempo FROM orcamento WHERE id_orcamento = ?");
                $stmt_orc->execute([$id_orcamento]);
                $orc_atual = $stmt_orc->fetch(PDO::FETCH_ASSOC);
                $valor_antigo = $orc_atual['valor_sessao'] ?? 0;
                $tempo_antigo = $orc_atual['estimativa_tempo'] ?? '';
            }

            // Atualiza a sessão salvando o valor histórico
            $sql_sessao = "UPDATE sessao SET status = 'Concluído', valor_sessao = ?, estimativa_tempo = ? WHERE id_sessao = ?";
            $pdo->prepare($sql_sessao)->execute([$valor_antigo, $tempo_antigo, $sessao_id]);

            // Finaliza o projeto
            $sql_projeto = "UPDATE projeto SET status = 'Finalizado' WHERE id_projeto = ?";
            $pdo->prepare($sql_projeto)->execute([$projeto_id]);
        }

        header("Location: ../pages/agenda.php?sucesso=concluido");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agenda.php?erro=bd");
        exit();
    }
}
