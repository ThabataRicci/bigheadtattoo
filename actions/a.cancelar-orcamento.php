<?php
session_start();
require_once '../includes/conexao.php'; // conecta ao banco de dados

// verifica se é cliente e está logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    header("Location: ../pages/login.php");
    exit();
}

// verifica se a requisição veio do formulário via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {

    $id_orcamento = $_POST['orcamento_id'];
    $id_usuario = $_SESSION['usuario_id'];

    try {

        // busca o orçamento p garantir que pertence ao cliente logado e AINDA está pendente
        // se o artista já aprovou ou recusou o cliente não pode mais apagar
        $sql_check = "SELECT id_orcamento, referencia_ideia 
                      FROM orcamento 
                      WHERE id_orcamento = ? AND id_usuario = ? AND (status = 'Pendente' OR status IS NULL)";

        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_orcamento, $id_usuario]);
        $orcamento = $stmt_check->fetch();

        // se a busca retornar resultado a ação é autorizada
        if ($orcamento) {

            // apaga a imagem anexada se existir
            if (!empty($orcamento['referencia_ideia'])) {
                $caminho_arquivo = "../imagens/orcamentos/" . $orcamento['referencia_ideia'];
                // verifica se o arquivo físico existe antes de tentar deletar
                if (file_exists($caminho_arquivo)) {
                    unlink($caminho_arquivo);
                }
            }

            // executa a exclusao
            $sql_delete = "DELETE FROM orcamento WHERE id_orcamento = ?";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute([$id_orcamento]);

            // redireciona de volta com um aviso de sucesso
            header("Location: ../pages/agendamentos-cliente.php?cancelado=orcamento");
            exit();
        } else {
            // se cair aqui tentou cancelar o orçamento de outra pessoa ou um orçamento já avaliado
            header("Location: ../pages/agendamentos-cliente.php?erro=permissao");
            exit();
        }
    } catch (PDOException $e) {
        // em caso de erro no banco de dados
        header("Location: ../pages/agendamentos-cliente.php?erro=bd");
        exit();
    }
} else {
    // se alguém tentar acessar a URL diretamente
    header("Location: ../pages/agendamentos-cliente.php");
    exit();
}
