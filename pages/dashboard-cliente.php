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

        <div class="row justify-content-center">

            <div class="col-lg-5 col-md-6 mb-4">
                <div class="card-resumo h-100 d-flex flex-column">
                    <h4>Acesso Rápido</h4>
                    <p class="text-white-50">Gerencie seus projetos ou agende uma nova tatuagem.</p>
                    <div class="mt-auto">
                        <div class="d-grid gap-2">
                            <a href="agendamentos-cliente.php" class="btn btn-primary">VER TODOS OS AGENDAMENTOS</a>
                            <a href="agenda.php" class="btn btn-outline-light">AGENDAR NOVA SESSÃO</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 col-md-6 mb-4">
                <h4>Seus Próximos Agendamentos</h4>

                <div class="card-resumo">
                    <p class="mb-1"><strong>Fechamento de Braço (Sessão 2/3)</strong></p>
                    <p class="text-white-50 small mb-0">Data: 25/10/2025 - Horário: 14:00</p>
                </div>

                <div class="card-resumo">
                    <p class="mb-1"><strong>Dragão Oriental (Sessão 1/3)</strong></p>
                    <p class="text-white-50 small mb-0">Data: 15/11/2025 - Horário: 10:00</p>
                </div>

            </div>

        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>