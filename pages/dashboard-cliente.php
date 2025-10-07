<?php
session_start();

// Simula o login do cliente
$_SESSION['loggedin'] = true;
$_SESSION['nome'] = "xxxxxxxxxxxxxxxxx";
$_SESSION['user_role'] = "cliente";

$titulo_pagina = "Meu Painel";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">

        <h2 class="text-center mb-5">PAINEL INICIAL</h2>

        <div class="row justify-content-center g-5">

            <div class="col-lg-7">
                <h3 class="mb-4">Próximos Agendamentos</h3>

                <div class="card-resumo mb-3">
                    <p class="mb-1"><strong>Fechamento de Braço</strong></p>
                    <p class="text-white-50 small mb-0">Data: 25/10/2025 - Horário: 14:00</p>
                </div>

                <div class="card-resumo mb-3">
                    <p class="mb-1"><strong>Rosa Fineline</strong></p>
                    <p class="text-white-50 small mb-0">Data: 15/11/2025 - Horário: 10:00</p>
                </div>
            </div>

            <div class="col-lg-4">
                <h3 class="mb-4 invisible">Ações</h3>

                <div class="card-resumo p-4">
                    <p class="text-white-50 mb-4">Acompanhe seus agendamentos ou solicite um novo orçamento.</p>
                    <div class="d-grid gap-2">
                        <a href="solicitar-orcamento.php" class="btn btn-primary">SOLICITAR ORÇAMENTO</a>
                        <a href="agendamentos-cliente.php" class="btn btn-outline-light">VER TODOS OS AGENDAMENTOS</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>