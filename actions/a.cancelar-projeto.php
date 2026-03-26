<?php
session_start();
require_once '../includes/conexao.php';

// redireciona se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // recebe qual ID foi enviado (Sessão ou Projeto)
    $id_sessao = !empty($_POST['sessao_id']) ? $_POST['sessao_id'] : null;
    $id_projeto = !empty($_POST['projeto_id']) ? $_POST['projeto_id'] : null;

    $perfil = $_SESSION['usuario_perfil'];
    $pagina_padrao = ($perfil == 'artista') ? 'dashboard-artista.php' : 'agendamentos-cliente.php';
    $pagina_origem = isset($_POST['origem']) && !empty($_POST['origem']) ? $_POST['origem'] : $pagina_padrao;

    if (!$id_sessao && !$id_projeto) {
        header("Location: ../pages/" . $pagina_origem);
        exit();
    }

    // Adiciona a tag de quem cancelou antes do motivo
    $quem_cancelou = ($perfil === 'artista') ? 'Artista' : 'Cliente';
    $motivo = "Cancelado pelo " . $quem_cancelou . ": " . trim($_POST['motivo']);

    try {
        // 1. Se vier por uma SESSÃO (projetos já agendados)
        if ($id_sessao) {
            // descobre qual é o projeto dessa sessão
            $stmt = $pdo->prepare("SELECT id_projeto FROM sessao WHERE id_sessao = ?");
            $stmt->execute([$id_sessao]);
            $id_projeto_encontrado = $stmt->fetchColumn();

            if ($id_projeto_encontrado) {
                $id_projeto = $id_projeto_encontrado; // salva o projeto para a próxima etapa

                // cancela a sessão específica
                $sql_sessao = "UPDATE sessao SET status = 'Cancelado', motivo_cancelamento = ? WHERE id_sessao = ?";
                $pdo->prepare($sql_sessao)->execute([$motivo, $id_sessao]);
            }
        }

        // 2. Se tivermos o ID do PROJETO (vindo da sessão acima ou direto do botão "ação requerida")
        if ($id_projeto) {
            // cancela o projeto inteiro
            $sql_projeto = "UPDATE projeto SET status = 'Cancelado', motivo_reagendamento = NULL WHERE id_projeto = ?";
            $pdo->prepare($sql_projeto)->execute([$id_projeto]);
        }

        header("Location: ../pages/" . $pagina_origem . "?sucesso=cancelado");
        exit();
    } catch (PDOException $e) {
        header("Location: ../pages/" . $pagina_origem . "?erro=bd");
        exit();
    }
} else {
    // 
    $perfil = $_SESSION['usuario_perfil'] ?? 'cliente';
    $pagina_padrao = ($perfil == 'artista') ? 'dashboard-artista.php' : 'agendamentos-cliente.php';
    header("Location: ../pages/" . $pagina_padrao);
    exit();
}
