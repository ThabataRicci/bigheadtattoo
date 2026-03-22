<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['sessao_id'])) {
    $id_sessao = $_POST['sessao_id'];
    $perfil = $_SESSION['usuario_perfil'];

    // Adiciona a tag de quem cancelou antes do motivo
    $quem_cancelou = ($perfil === 'artista') ? 'Artista' : 'Cliente';
    $motivo = "Cancelado pelo " . $quem_cancelou . ": " . trim($_POST['motivo']);

    try {
        $stmt = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
        $stmt->execute([$id_sessao]);
        $id_projeto = $stmt->fetchColumn();

        if ($id_projeto) {
            // cancela a sessão
            $sql_sessao = "UPDATE sessao SET status = 'Cancelado', motivo_cancelamento = ? WHERE id_sessao = ?";
            $pdo->prepare($sql_sessao)->execute([$motivo, $id_sessao]);

            // cancela o projeto inteiro 
            $sql_projeto = "UPDATE projeto SET status = 'Cancelado', motivo_reagendamento = NULL WHERE id_projeto = ?";
            $pdo->prepare($sql_projeto)->execute([$id_projeto]);
        }

        $pagina = $perfil == 'artista' ? 'dashboard-artista.php' : 'agendamentos-cliente.php';
        header("Location: ../pages/" . $pagina . "?sucesso=cancelado");
        exit();
    } catch (PDOException $e) {
        exit();
    }
}
