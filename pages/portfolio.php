<?php
session_start();
$titulo_pagina = "Portfólio";
include '../includes/header.php';
include '../actions/a.portfolio.php';
?>

<?php
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    $link_prefix = '';

    echo '<div class="submenu-painel">';

    if ($_SESSION['usuario_perfil'] == 'artista') {
        echo '<a href="' . $link_prefix . 'dashboard-artista.php" class="' . ($pagina_ativa == 'dashboard-artista.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="' . $link_prefix . 'agenda.php" class="' . ($pagina_ativa == 'agenda.php' ? 'active' : '') . '">Agenda</a>';
        echo '<a href="' . $link_prefix . 'portfolio-artista.php" class="' . ($pagina_ativa == 'portfolio-artista.php' ? 'active' : '') . '">Portfólio</a>';
        echo '<a href="' . $link_prefix . 'relatorios-artista.php" class="' . ($pagina_ativa == 'relatorios-artista.php' ? 'active' : '') . '">Relatórios</a>';
        echo '<a href="' . $link_prefix . 'configuracoes-artista.php" class="' . ($pagina_ativa == 'configuracoes-artista.php' ? 'active' : '') . '">Configurações</a>';
    } else {
        echo '<a href="' . $link_prefix . 'dashboard-cliente.php" class="' . ($pagina_ativa == 'dashboard-cliente.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="' . $link_prefix . 'agendamentos-cliente.php" class="' . ($pagina_ativa == 'agendamentos-cliente.php' ? 'active' : '') . '">Meus Agendamentos</a>';
        echo '<a href="' . $link_prefix . 'solicitar-orcamento.php" class="' . ($pagina_ativa == 'solicitar-orcamento.php' ? 'active' : '') . '">Orçamento</a>';
        echo '<a href="' . $link_prefix . 'configuracoes-cliente.php" class="' . ($pagina_ativa == 'configuracoes-cliente.php' ? 'active' : '') . '">Configurações</a>';
    }
    echo '</div>';
}
?>

<style>
    /* Remove o contorno azul e a cor de fundo padrão do Bootstrap ao focar na paginação */
    .pagination .page-link:focus {
        box-shadow: none !important;
        background-color: transparent !important;
        color: #fff !important;
        /* Mantém a cor do texto branca */
    }

    /* Garante que o hover também não aplique fundo azul estranho, se houver */
    .pagination .page-link:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
    }
</style>

<main>
    <div class="container text-center my-5 py-5">

        <h2 class="mb-5">GALERIA DE TRABALHOS</h2>

        <div class="filtros-portfolio mb-5">
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    Filtrar por Estilo
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="?estilo=todos">Todos</a></li>
                    <?php if (isset($lista_estilos)): ?>
                        <?php foreach ($lista_estilos as $est): ?>
                            <li><a class="dropdown-item" href="?estilo=<?= urlencode($est['nome']) ?>"><?= htmlspecialchars($est['nome']) ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="row">
            <?php if (isset($trabalhos) && count($trabalhos) > 0): ?>
                <?php foreach ($trabalhos as $item): ?>
                    <div class="col-lg-3 col-md-4 col-6 mb-4">
                        <div class="portfolio-item">
                            <img src="../imagens/portfolio/<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="img-fluid">
                            <div class="portfolio-detalhes-overlay">
                                <h5 class="detalhes-titulo"><?= htmlspecialchars($item['titulo']) ?></h5>
                                <p class="detalhes-info">Estilo: <?= htmlspecialchars($item['estilo_nome']) ?></p>
                                <p class="detalhes-info">Tempo: <?= htmlspecialchars($item['tempo_execucao']) ?></p>
                                <p class="detalhes-info">Sessões: <?= htmlspecialchars($item['qtd_sessoes']) ?></p>
                                <p class="detalhes-info">Local: <?= htmlspecialchars($item['local_corpo']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-white col-12">Nenhum trabalho encontrado.</p>
            <?php endif; ?>
        </div>

        <?php if (isset($total_paginas) && $total_paginas >= 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">

                    <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_atual - 1 ?>&estilo=<?= urlencode($estilo_filtro ?? 'todos') ?>">Anterior</a>
                    </li>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= ($pagina_atual == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i ?>&estilo=<?= urlencode($estilo_filtro ?? 'todos') ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_atual + 1 ?>&estilo=<?= urlencode($estilo_filtro ?? 'todos') ?>">Próximo</a>
                    </li>

                </ul>
            </nav>
        <?php endif; ?>

    </div>
</main>

<?php include '../includes/footer.php'; ?>