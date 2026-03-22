<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $id_sessao = $_POST['sessao_id'];
    $perfil = $_SESSION['usuario_perfil'];

    // Adiciona a tag de quem reagendou antes do motivo
    $quem_reagendou = ($perfil === 'artista') ? 'Artista' : 'Cliente';
    $motivo = "Reagendado pelo " . $quem_reagendou . ": " . trim($_POST['motivo']);

    try {
        // 1. descobre a qual projeto a sessão pertence
        $stmt = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
        $stmt->execute([$id_sessao]);
        $id_projeto = $stmt->fetchColumn();

        if ($id_projeto) {
            // 2. cancela a sessão atual
            $sql_sessao = "UPDATE sessao SET status = 'Cancelado', motivo_cancelamento = ? WHERE id_sessao = ?";
            $pdo->prepare($sql_sessao)->execute([$motivo, $id_sessao]);

            // 3. volta o projeto para pendente e salva o motivo 
            $sql_projeto = "UPDATE projeto SET status = 'Agendamento Pendente', motivo_reagendamento = ? WHERE id_projeto = ?";
            $pdo->prepare($sql_projeto)->execute([$motivo, $id_projeto]);
        }

        if ($perfil == 'cliente') {
            // se o cliente reagendou, joga ele direto no calendário para escolher a data na hora
            header("Location: ../pages/agenda.php?projeto_id=" . $id_projeto);
        } else {
            // se o artista reagendou, volta pro painel 
            header("Location: ../pages/dashboard-artista.php?sucesso=reagendado");
        }
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/dashboard-artista.php?erro=bd");
        exit();
    }
}
