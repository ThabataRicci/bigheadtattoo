<?php
session_start();

$_SESSION['loggedin'] = true;
$_SESSION['nome'] = "xxxxxxxxxxxxxxxxx";
$_SESSION['user_role'] = "artista";

$titulo_pagina = "Painel de Controle";
include 'includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="text-center">
        </div>

        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="card-resumo">
                    <h3>X</h3>
                    <p class="text-white-50 mb-0">Solicitações para Aprovar</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-resumo">
                    <h3>X</h3>
                    <p class="text-white-50 mb-0">Sessões na Semana</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-resumo">
                    <h3>X</h3>
                    <p class="text-white-50 mb-0">Novos Clientes no Mês</p>
                </div>
            </div>
        </div>

        <hr class="my-5">

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card-resumo">
                    <h4>Gerenciar Agenda</h4>
                    <p class="text-white-50">Você tem X solicitações para aprovar.</p>
                    <a href="agenda-artista.php" class="btn btn-outline-light">Ver Agenda</a>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card-resumo">
                    <h4>Gerenciar Portfólio</h4>
                    <p class="text-white-50">Adicione, edite ou remova fotos da sua galeria.</p>
                    <a href="portfolio-artista.php" class="btn btn-outline-light">Ver Portfólio</a>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card-resumo">
                    <h4>Relatórios</h4>
                    <p class="text-white-50">Acompanhe as sessões do estúdio e clientes cadastrados.</p>
                    <a href="relatorios-artista.php" class="btn btn-outline-light">Acessar Relatórios</a>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card-resumo">
                    <h4>Configurações</h4>
                    <p class="text-white-50">Gerencie seu perfil e estilos.</p>
                    <a href="configuracoes-artista.php" class="btn btn-outline-light">Acessar Configurações</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?>