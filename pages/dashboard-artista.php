<?php
session_start();

$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "artista";

$titulo_pagina = "Painel de Controle";
include '../includes/header.php';
?>

<?php
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {

    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    $link_prefix = '';

    echo '<div class="submenu-painel">';

    if ($_SESSION['user_role'] == 'artista') {
        // menu do artista
        echo '<a href="' . $link_prefix . 'dashboard-artista.php" class="' . ($pagina_ativa == 'dashboard-artista.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="' . $link_prefix . 'agenda.php" class="' . ($pagina_ativa == 'agenda.php' ? 'active' : '') . '">Agenda</a>';
        echo '<a href="' . $link_prefix . 'portfolio-artista.php" class="' . ($pagina_ativa == 'portfolio-artista.php' ? 'active' : '') . '">Portfólio</a>';
        echo '<a href="' . $link_prefix . 'relatorios-artista.php" class="' . ($pagina_ativa == 'relatorios-artista.php' ? 'active' : '') . '">Relatórios</a>';
        echo '<a href="' . $link_prefix . 'configuracoes-artista.php" class="' . ($pagina_ativa == 'configuracoes-artista.php' ? 'active' : '') . '">Configurações</a>';
    } else {
        // menu do cliente 
        echo '<a href="' . $link_prefix . 'dashboard-cliente.php" class="' . ($pagina_ativa == 'dashboard-cliente.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="' . $link_prefix . 'agendamentos-cliente.php" class="' . ($pagina_ativa == 'agendamentos-cliente.php' ? 'active' : '') . '">Meus Agendamentos</a>';
        echo '<a href="' . $link_prefix . 'solicitar-orcamento.php" class="' . ($pagina_ativa == 'solicitar-orcamento.php' ? 'active' : '') . '">Orçamento</a>';
        echo '<a href="' . $link_prefix . 'configuracoes-cliente.php" class="' . ($pagina_ativa == 'configuracoes-cliente.php' ? 'active' : '') . '">Configurações</a>';
    }

    echo '</div>';
}
?>

<main>
    <div class="container my-5 py-5">



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
                    <p class="text-white-50">Visualizar agendamentos e pedidos de orçamento.</p>
                    <a href="agenda.php" class="btn btn-outline-light">Ver Agenda</a>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card-resumo">
                    <h4>Gerenciar Portfólio</h4>
                    <p class="text-white-50">Adicione, edite ou remova fotos do portfólio.</p>
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
include '../includes/footer.php';
?>