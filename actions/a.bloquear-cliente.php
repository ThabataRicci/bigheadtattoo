<?php
session_start();
require_once '../includes/conexao.php';

// somente artista logado pode bloquear/desbloquear clientes
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
    $acao = $_POST['acao'] ?? ''; // pode ser 'bloquear' ou 'desbloquear'

    if ($id_cliente > 0 && ($acao === 'bloquear' || $acao === 'desbloquear')) {

        $novo_status = ($acao === 'bloquear') ? 'Bloqueado' : 'Ativo';

        try {
            $pdo->beginTransaction();

            // 1. Atualiza o status do usuário (apenas se for perfil 'cliente')
            $sql = "UPDATE usuario SET status = ? WHERE id_usuario = ? AND perfil = 'cliente'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$novo_status, $id_cliente]);

            // 2. Se a ação for bloquear, limpa a agenda e os projetos pendentes
            if ($acao === 'bloquear') {

                // A. Cancela todas as sessões agendadas
                $stmt_cancel_sessao = $pdo->prepare("
                    UPDATE sessao s
                    JOIN projeto p ON s.id_projeto = p.id_projeto
                    SET s.status = 'Cancelado', 
                        s.motivo_cancelamento = 'Conta do cliente bloqueada'
                    WHERE p.id_usuario = ? AND s.status = 'Agendado'
                ");
                $stmt_cancel_sessao->execute([$id_cliente]);

                // B. Busca todos os projetos que ainda estão abertos
                $stmt_proj = $pdo->prepare("SELECT id_projeto FROM projeto WHERE id_usuario = ? AND status NOT IN ('Finalizado', 'Cancelado')");
                $stmt_proj->execute([$id_cliente]);
                $projetos_abertos = $stmt_proj->fetchAll();

                foreach ($projetos_abertos as $proj) {
                    // Verifica se o projeto já tem alguma sessão concluída
                    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM sessao WHERE id_projeto = ? AND status = 'Concluído'");
                    $stmt_check->execute([$proj['id_projeto']]);
                    $tem_concluida = $stmt_check->fetchColumn() > 0;

                    if ($tem_concluida) {
                        // Tem sessão concluída: finaliza para manter no faturamento
                        $stmt_upd_proj = $pdo->prepare("UPDATE projeto SET status = 'Finalizado' WHERE id_projeto = ?");
                    } else {
                        // Nenhuma sessão concluída: cancela o projeto por completo
                        $stmt_upd_proj = $pdo->prepare("UPDATE projeto SET status = 'Cancelado', motivo_cancelamento = 'Conta do cliente bloqueada' WHERE id_projeto = ?");
                    }
                    $stmt_upd_proj->execute([$proj['id_projeto']]);
                }
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Erro silencioso - em um sistema de produção, você poderia logar o $e->getMessage()
        }
    }
}

// Redireciona de volta passando o status na URL para exibir a mensagem
$mensagem = ($acao === 'bloquear') ? 'bloqueado' : 'desbloqueado';
header("Location: ../pages/relatorios-artista.php?aba=clientes&sucesso=" . $mensagem);
exit();
