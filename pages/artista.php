<?php
session_start();
$titulo_pagina = "O Artista";
include '../includes/header.php';
?>

<?php
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {

    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    $link_prefix = 'pages/';

    echo '<div class="submenu-painel">';

    if ($_SESSION['user_role'] == 'artista') {
        // menu do artista
        echo '<a href="' . $link_prefix . 'dashboard-artista.php" class="' . ($pagina_ativa == 'index.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="' . $link_prefix . 'agenda.php">Agenda</a>';
        echo '<a href="' . $link_prefix . 'portfolio-artista.php">Portfólio</a>';
        echo '<a href="' . $link_prefix . 'relatorios-artista.php">Relatórios</a>';
        echo '<a href="' . $link_prefix . 'configuracoes-artista.php">Configurações</a>';
    } else {
        // menu do cliente
        echo '<a href="' . $link_prefix . 'dashboard-cliente.php" class="' . ($pagina_ativa == 'index.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="' . $link_prefix . 'agendamentos-cliente.php">Meus Agendamentos</a>';
        echo '<a href="' . $link_prefix . 'solicitar-orcamento.php">Orçamento</a>';
        echo '<a href="' . $link_prefix . 'configuracoes-cliente.php">Configurações</a>';
    }

    echo '</div>';
}
?>

<main>
    <div class="container my-5 py-5">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <img src="../imagens/foto_artista.png" class="img-fluid rounded-circle mb-4 foto-perfil-artista" alt="Foto do Artista">
            </div>
            <div class="col-md-8">
                <h2 class="display-4">DANIEL KBÇA</h2>
                <p class="especialidades-artista">Especialidades: Maori, Geométrico, Escrita</p>
                <p>Apaixonado por transformar ideias em arte na pele, Daniel Kbça construiu sua trajetória unindo técnica, criatividade e atenção aos detalhes. No estúdio, cada trabalho é pensado junto com o cliente, respeitando seu estilo e valorizando a originalidade. Seja em traços finos ou projetos maiores, a prioridade é sempre entregar uma tatuagem única, feita com cuidado e profissionalismo.</p>
            </div>
        </div>
        <hr class="my-5">
        <div class="text-center">
            <h2 class="mb-5">TRABALHOS DO ARTISTA</h2>
            <div class="row">
                <div class="col-lg-3 col-md-4 col-6 mb-4">
                    <div class="portfolio-item">
                        <img src="../imagens/exemplo1.jpg">
                        <div class="portfolio-detalhes-overlay">
                            <h5 class="detalhes-titulo">Dragão</h5>
                            <p class="detalhes-info">Estilo: Oriental</p>
                            <p class="detalhes-info">Tempo: 6 horas</p>
                            <p class="detalhes-info">Local: Costas</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-6 mb-4">
                    <div class="portfolio-item">
                        <img src="../imagens/exemplo2.jpg">
                        <div class="portfolio-detalhes-overlay">
                            <h5 class="detalhes-titulo">Rosa</h5>
                            <p class="detalhes-info">Estilo: Fineline</p>
                            <p class="detalhes-info">Tempo: 6 horas</p>
                            <p class="detalhes-info">Local: Braço</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-6 mb-4">
                    <div class="portfolio-item"></div>
                </div>
                <div class="col-lg-3 col-md-4 col-6 mb-4">
                    <div class="portfolio-item"></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-4">
            <a href="portfolio.php" class="btn btn-outline-light">VER PORTFÓLIO COMPLETO</a>
        </div>

</main>

<?php
include '../includes/footer.php';
?>