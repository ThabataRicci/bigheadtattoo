<?php
session_start();
require_once 'includes/conexao.php'; // Conecta ao banco de dados

$titulo_pagina = "Página Inicial";

// --- BUSCA OS DESTAQUES DO PORTFÓLIO NO BANCO ---
try {
    // Busca os 4 últimos trabalhos adicionados no portfólio
    $sql_portfolio = "SELECT titulo, imagem, estilo, tempo_execucao, qtd_sessoes, local_corpo FROM portfolio ORDER BY id_portfolio DESC LIMIT 4";
    $stmt_portfolio = $pdo->query($sql_portfolio);
    $destaques_portfolio = $stmt_portfolio->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $destaques_portfolio = []; // Se der erro, cria uma lista vazia para não quebrar a tela
}

include 'includes/header.php';
?>

<?php

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {

    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    $link_prefix = 'pages/';

    echo '<div class="submenu-painel">';

    if ($_SESSION['usuario_perfil'] == 'artista') {
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
    <section class="secao-principal d-flex justify-content-center align-items-center text-center">
        <div class="container">
            <h1 class="display-3">BIG HEAD TATTOO</h1>
            <a href="pages/solicitar-orcamento.php" class="btn btn-outline-light btn-lg mt-3">SOLICITAR ORÇAMENTO</a>
        </div>
    </section>

    <section id="sobre" class="container text-center my-5 py-5">
        <h2>SOBRE O ARTISTA</h2>
        <p class="lead">Apaixonado por transformar ideias em arte na pele, Daniel Kbça construiu sua trajetória unindo técnica, criatividade e atenção aos detalhes. No estúdio, cada trabalho é pensado junto com o cliente, respeitando seu estilo e valorizando a originalidade. Seja em traços finos ou projetos maiores, a prioridade é sempre entregar uma tatuagem única, feita com cuidado e profissionalismo.</p>
    </section>

    <section class="container text-center my-5 py-5">
        <h2>DESTAQUES DO PORTFÓLIO</h2>
        <div class="row justify-content-center">

            <?php if (empty($destaques_portfolio)): ?>
                <p class="text-white-50 mt-4">Nenhum trabalho adicionado ao portfólio ainda.</p>
            <?php else: ?>
                <?php foreach ($destaques_portfolio as $item): ?>
                    <div class="col-lg-3 col-md-4 col-6 mb-4">
                        <div class="portfolio-item">
                            <img src="imagens/portfolio/<?php echo htmlspecialchars($item['imagem']); ?>" alt="<?php echo htmlspecialchars($item['titulo']); ?>">
                            <div class="portfolio-detalhes-overlay">
                                <h5 class="detalhes-titulo"><?php echo htmlspecialchars($item['titulo']); ?></h5>
                                <p class="detalhes-info mb-0">Estilo: <?php echo htmlspecialchars($item['estilo']); ?></p>
                                <p class="detalhes-info mb-0">Tempo: <?php echo htmlspecialchars($item['tempo_execucao']); ?></p>
                                <p class="detalhes-info mb-0">Sessões: <?php echo htmlspecialchars($item['qtd_sessoes']); ?></p>
                                <p class="detalhes-info mb-0">Local: <?php echo htmlspecialchars($item['local_corpo']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
        <div class="d-flex justify-content-center mt-4">
            <a href="pages/portfolio.php" class="btn btn-outline-light">VER PORTFÓLIO COMPLETO</a>
        </div>
    </section>
</main>

<?php
include 'includes/footer.php';
?>