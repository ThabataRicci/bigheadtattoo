<?php
session_start();
$titulo_pagina = "Portfólio";
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
    <div class="container text-center my-5 py-5">

        <h2 class="mb-5">GALERIA DE TRABALHOS</h2>

        <div class="filtros-portfolio mb-5">
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    Filtrar por Estilo
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="#" onclick="filtrarPortfolio('todos')">Todos</a></li>
                    <li><a class="dropdown-item" href="#" onclick="filtrarPortfolio('oriental')">Oriental</a></li>
                    <li><a class="dropdown-item" href="#" onclick="filtrarPortfolio('fineline')">Fineline</a></li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-4 col-6 mb-4 item-filtrado" data-style="oriental">
                <div class="portfolio-item">
                    <img src="../imagens/exemplo1.jpg">
                    <div class="portfolio-detalhes-overlay">
                        <h5 class="detalhes-titulo">Dragão</h5>
                        <p class="detalhes-info">Estilo: Oriental</p>
                        <p class="detalhes-info">Tempo: 6 horas</p>
                        <p class="detalhes-info">Sessões: 2</p>
                        <p class="detalhes-info">Local: Costas</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-6 mb-4 item-filtrado" data-style="fineline">
                <div class="portfolio-item">
                    <img src="../imagens/exemplo2.jpg">
                    <div class="portfolio-detalhes-overlay">
                        <h5 class="detalhes-titulo">Rosa</h5>
                        <p class="detalhes-info">Estilo: Fineline</p>
                        <p class="detalhes-info">Tempo: 6 horas</p>
                        <p class="detalhes-info">Sessões: 1</p>
                        <p class="detalhes-info">Local: Braço</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-6 mb-4 item-filtrado" data-style="oriental">
                <div class="portfolio-item"></div>
            </div>
            <div class="col-lg-3 col-md-4 col-6 mb-4 item-filtrado" data-style="fineline">
                <div class="portfolio-item"></div>
            </div>
            <div class="col-lg-3 col-md-4 col-6 mb-4 item-filtrado" data-style="fineline">
                <div class="portfolio-item"></div>
            </div>
            <div class="col-lg-3 col-md-4 col-6 mb-4 item-filtrado" data-style="oriental">
                <div class="portfolio-item"></div>
            </div>

        </div>

        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Próximo</a></li>
            </ul>
        </nav>

    </div>
</main>

<script>
    function filtrarPortfolio(estilo) {
        const items = document.querySelectorAll('.item-filtrado');

        items.forEach(item => {
            if (estilo === 'todos' || item.getAttribute('data-style') === estilo) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
</script>

<?php
include '../includes/footer.php';
?>