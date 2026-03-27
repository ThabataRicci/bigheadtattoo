<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['projeto_id'])) {
    $projeto_id = $_POST['projeto_id'];

    // Recebe os novos valores (que vão valer para a PRÓXIMA sessão)
    $valor_post = $_POST['valor_sessao'] ?? '0';
    $tempo_post = $_POST['estimativa_tempo'] ?? '';

    try {
        // 1. Descobrir qual é o orçamento vinculado a esse projeto
        $stmt_proj = $pdo->prepare("SELECT id_orcamento FROM projeto WHERE id_projeto = ?");
        $stmt_proj->execute([$projeto_id]);
        $id_orcamento = $stmt_proj->fetchColumn();

        if ($id_orcamento) {
            // 2. Guarda o valor ANTIGO
            $stmt_orc = $pdo->prepare("SELECT valor_sessao, estimativa_tempo FROM orcamento WHERE id_orcamento = ?");
            $stmt_orc->execute([$id_orcamento]);
            $orc_atual = $stmt_orc->fetch(PDO::FETCH_ASSOC);

            $valor_antigo = $orc_atual['valor_sessao'] ?? 0;
            $tempo_antigo = $orc_atual['estimativa_tempo'] ?? '';

            // 3. Conclui a sessão de hoje guardando o valor histórico dela
            $sql_sessao = "UPDATE sessao SET status = 'Concluído', valor_sessao = ?, estimativa_tempo = ? WHERE id_projeto = ? AND status = 'Agendado'";
            $pdo->prepare($sql_sessao)->execute([$valor_antigo, $tempo_antigo, $projeto_id]);

            // 4. Atualiza a regra geral (orçamento) para as PRÓXIMAS sessões
            $valor_formatado = str_replace('.', '', $valor_post);
            $valor_formatado = str_replace(',', '.', $valor_formatado);
            $valor_float = (float)$valor_formatado;

            $sql_update_orc = "UPDATE orcamento SET valor_sessao = ?, estimativa_tempo = ? WHERE id_orcamento = ?";
            $pdo->prepare($sql_update_orc)->execute([$valor_float, $tempo_post, $id_orcamento]);
        }

        // 5. Volta o projeto para a Ação Requerida do cliente para ele escolher nova data
        $sql_projeto = "UPDATE projeto SET status = 'Agendamento Pendente', motivo_reagendamento = NULL WHERE id_projeto = ?";
        $pdo->prepare($sql_projeto)->execute([$projeto_id]);

        header("Location: ../pages/agenda.php?sucesso=sessao_liberada");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/agenda.php?erro=bd");
        exit();
    }
}
