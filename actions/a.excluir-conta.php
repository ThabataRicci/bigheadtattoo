<?php
session_start();
require_once '../includes/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['usuario_id'])) {
    $id = $_SESSION['usuario_id'];
    $perfil = $_SESSION['usuario_perfil'];
    $senha_digitada = $_POST['senha_confirmacao'];

    // 1. Busca a senha para conferência
    $stmt = $pdo->prepare("SELECT senha FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha_digitada, $user['senha'])) {
        try {
            $pdo->beginTransaction();

            // --- LÓGICA DE ENCERRAMENTO DE PROJETOS E SESSÕES ---

            // A. Cancelar todas as sessões que estão apenas 'Agendadas'
            $stmt_cancel_sessao = $pdo->prepare("
                UPDATE sessao s
                JOIN projeto p ON s.id_projeto = p.id_projeto
                SET s.status = 'Cancelado', 
                    s.motivo_cancelamento = 'Conta do cliente excluída'
                WHERE p.id_usuario = ? AND s.status = 'Agendado'
            ");
            $stmt_cancel_sessao->execute([$id]);

            // B. Buscar todos os projetos abertos deste cliente
            $stmt_proj = $pdo->prepare("SELECT id_projeto FROM projeto WHERE id_usuario = ? AND status NOT IN ('Finalizado', 'Cancelado')");
            $stmt_proj->execute([$id]);
            $projetos_abertos = $stmt_proj->fetchAll();

            foreach ($projetos_abertos as $proj) {
                // Verifica se este projeto específico tem alguma sessão concluída
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM sessao WHERE id_projeto = ? AND status = 'Concluído'");
                $stmt_check->execute([$proj['id_projeto']]);
                $tem_concluida = $stmt_check->fetchColumn() > 0;

                if ($tem_concluida) {
                    // Se já tatuou algo, finaliza o projeto para manter no faturamento
                    $stmt_upd_proj = $pdo->prepare("UPDATE projeto SET status = 'Finalizado' WHERE id_projeto = ?");
                } else {
                    // Se nunca tatuou nada, cancela o projeto e grava o motivo
                    $stmt_upd_proj = $pdo->prepare("UPDATE projeto SET status = 'Cancelado', motivo_cancelamento = 'Conta do cliente excluída' WHERE id_projeto = ?");
                }
                $stmt_upd_proj->execute([$proj['id_projeto']]);
            }

            // --- FIM DA LÓGICA DE ENCERRAMENTO ---

            // SOFT DELETE do Usuário (Corrigido o CONCAT do e-mail)
            $sql_user = "UPDATE usuario SET 
                        status = 'Excluido', 
                        email = CONCAT('id', id_usuario, '_excluido@bigheadtattoo.com'), 
                        telefone = '',
                        senha = '---' 
                        WHERE id_usuario = ?";

            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute([$id]);

            $pdo->commit();

            session_destroy();
            header("Location: ../pages/login.php?sucesso=conta_excluida");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $url = ($perfil == 'artista') ? 'configuracoes-artista.php' : 'configuracoes-cliente.php';
            // Em produção, voltamos para a URL com erro. 
            // Se quiser debugar de novo, use: exit($e->getMessage());
            header("Location: ../pages/$url?erro=excluir");
            exit();
        }
    } else {
        $url = ($perfil == 'artista') ? 'configuracoes-artista.php' : 'configuracoes-cliente.php';
        header("Location: ../pages/$url?erro=senha_excluir");
        exit();
    }
}
