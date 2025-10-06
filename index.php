<?php
session_start();
$titulo_pagina = "Página Inicial";
include 'includes/header.php';
?>

<main>
    <section class="secao-principal d-flex justify-content-center align-items-center text-center">
        <div class="container">
            <h1 class="display-3">BIG HEAD TATTOO</h1>
            <a href="pages/agenda.php" class="btn btn-outline-light btn-lg mt-3">AGENDAR SESSÃO</a>
        </div>
    </section>

    <section id="sobre" class="container text-center my-5 py-5">
        <h2>SOBRE O ARTISTA</h2>
        <p class="lead">Apaixonado por transformar ideias em arte na pele, Daniel Kbça construiu sua trajetória unindo técnica, criatividade e atenção aos detalhes. No estúdio, cada trabalho é pensado junto com o cliente, respeitando seu estilo e valorizando a originalidade. Seja em traços finos ou projetos maiores, a prioridade é sempre entregar uma tatuagem única, feita com cuidado e profissionalismo.</p>
    </section>

    <section class="container text-center my-5 py-5">
        <h2>DESTAQUES DO PORTFÓLIO</h2>
        <div class="row">
            <div class="col-lg-3 col-md-4 col-6 mb-4">
                <div class="portfolio-item">
                    <img src="imagens/exemplo1.jpg">
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
                    <img src="imagens/exemplo2.jpg">
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
            <div class="d-flex justify-content-center mt-4">
                <a href="pages/portfolio.php" class="btn btn-outline-light">VER PORTFÓLIO COMPLETO</a>
            </div>

    </section>
</main>

<?php
include 'includes/footer.php';
?>