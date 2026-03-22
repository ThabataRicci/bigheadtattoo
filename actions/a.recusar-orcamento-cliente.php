<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['orcamento_id'])) {
    $id_orcamento = $_POST['orcamento_id'];
    $tipo_recusa = $_POST['tipo_recusa'];

    try {
        if ($tipo_recusa == 'preco') {
            // Volta pro artista e queima o cartucho de negociação
            $sql = "UPDATE orcamento SET status = 'Negociacao', tentativas_negociacao = tentativas_negociacao + 1 WHERE id_orcamento = ?";
            $pdo->prepare($sql)->execute([$id_orcamento]);
        } else {
            // Recusa por outro motivo (Encerra o fluxo)
            $motivo = $_POST['motivo_cancelamento_cliente'];
            $sql = "UPDATE orcamento SET status = 'Cancelado pelo Cliente', motivo_cancelamento_cliente = ? WHERE id_orcamento = ?";
            $pdo->prepare($sql)->execute([$motivo, $id_orcamento]);
        }
        header("Location: ../pages/agendamentos-cliente.php?sucesso=recusado");
        exit();
    } catch (PDOException $e) {
        exit();
    }
}
