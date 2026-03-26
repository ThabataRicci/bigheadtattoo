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
            // atualiza apenas se for perfil 'cliente'
            $sql = "UPDATE usuario SET status = ? WHERE id_usuario = ? AND perfil = 'cliente'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$novo_status, $id_cliente]);
        } catch (PDOException $e) {
        }
    }
}

// Redireciona de volta passando o status na URL para exibir a mensagem
$mensagem = ($acao === 'bloquear') ? 'bloqueado' : 'desbloqueado';
header("Location: ../pages/relatorios-artista.php?aba=clientes&sucesso=" . $mensagem);
exit();
