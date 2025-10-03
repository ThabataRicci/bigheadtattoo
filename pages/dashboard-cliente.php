<?php
session_start();

$_SESSION['loggedin'] = true;
$_SESSION['nome'] = "xxxxxxxxxxxxxxxxx";
$_SESSION['user_role'] = "cliente";

$titulo_pagina = "Meu Painel";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="text-center">
        </div>

        <div class="row">
            <div class="col-md-5 mb-4">
                <h4>Agende sua Próxima Sessão</h4>
                <p class="text-white-50">Veja os horários disponíveis.</p>
                <a href="agenda.php" class="btn btn-outline-light">Agendar Nova Sessão</a>
            </div>

            <div class="col-md-7">
                <h4>Próximos Agendamentos</h4>
                <div class="card-resumo">
                    <p class="mb-1"><strong>Sessão agendada.</strong></p>
                    <p class="text-white-50 small mb-0">Data: [DATA] - Horário: [HORARIO]</p>
                </div>
                <div class="card-resumo">
                    <p class="mb-1"><strong>Sessão agendada.</strong></p>
                    <p class="text-white-50 small mb-0">Data: [DATA] - Horário: [HORARIO]</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>