<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $id_sessao = $_POST['sessao_id'];
    $perfil = $_SESSION['usuario_perfil'];

    // adiciona a tag de quem reagendou antes do motivo
    $quem_reagendou = ($perfil === 'artista') ? 'Artista' : 'Cliente';
    $motivo = "Reagendado pelo " . $quem_reagendou . ": " . trim($_POST['motivo']);

    try {
        // 1. descobre a qual projeto a sessão pertence e pega os valores atuais
        $stmt = $pdo->prepare("SELECT id_projeto, valor_sessao, estimativa_tempo FROM sessao WHERE id_sessao = ?");
        $stmt->execute([$id_sessao]);
        $sessao_antiga = $stmt->fetch(PDO::FETCH_ASSOC);

        $id_projeto = $sessao_antiga['id_projeto'] ?? 0;

        if ($id_projeto) {
            // Busca o id_orcamento vinculado ao projeto para pegar os valores base, caso a sessão não os tenha
            $stmt_proj = $pdo->prepare("SELECT id_orcamento FROM projeto WHERE id_projeto = ?");
            $stmt_proj->execute([$id_projeto]);
            $id_orcamento = $stmt_proj->fetchColumn();

            $valor_base = 0;
            $tempo_base = '';

            if ($id_orcamento) {
                $stmt_orc = $pdo->prepare("SELECT valor_sessao, estimativa_tempo FROM orcamento WHERE id_orcamento = ?");
                $stmt_orc->execute([$id_orcamento]);
                $orc_atual = $stmt_orc->fetch(PDO::FETCH_ASSOC);
                $valor_base = $orc_atual['valor_sessao'] ?? 0;
                $tempo_base = $orc_atual['estimativa_tempo'] ?? '';
            }

            // Define quais valores guardar no histórico da sessão cancelada
            $valor_historico = $sessao_antiga['valor_sessao'] ?? $valor_base;
            $tempo_historico = $sessao_antiga['estimativa_tempo'] ?? $tempo_base;

            // 2. cancela a sessão atual e salva o valor/tempo nela para o histórico
            $sql_sessao = "UPDATE sessao SET status = 'Cancelado', motivo_cancelamento = ?, valor_sessao = ?, estimativa_tempo = ? WHERE id_sessao = ?";
            $pdo->prepare($sql_sessao)->execute([$motivo, $valor_historico, $tempo_historico, $id_sessao]);

            // 3. volta o projeto para pendente e salva o motivo 
            $sql_projeto = "UPDATE projeto SET status = 'Agendamento Pendente', motivo_reagendamento = ? WHERE id_projeto = ?";
            $pdo->prepare($sql_projeto)->execute([$motivo, $id_projeto]);
        }

        if ($perfil == 'cliente') {
            // CORREÇÃO: Levar o cliente para a página de agendamento correta
            header("Location: ../pages/agendar-sessao-cliente.php?projeto_id=" . $id_projeto);
        } else {
            // se o artista reagendou, volta pro painel dele
            header("Location: ../pages/dashboard-artista.php?sucesso=reagendado");
        }
        exit();
    } catch (PDOException $e) {
        $pagina_erro = ($perfil == 'cliente') ? 'agendamentos-cliente.php' : 'dashboard-artista.php';
        header("Location: ../pages/" . $pagina_erro . "?erro=bd");
        exit();
    }
}
