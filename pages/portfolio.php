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
    /* --- ESTILOS DA PAGINAÇÃO TRANSPARENTE --- */
    /* 1. Borda dos botões NÃO CLICADOS (padrão) */
    .pagination .page-link {
        background-color: transparent !important;
        border: 1px solid #2c2c2c !important;
        color: #aaa !important;
        margin: 0 4px;
        border-radius: 6px !important;
        transition: all 0.3s ease;
    }

    /* Efeito ao passar o mouse */
    .pagination .page-link:hover {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #ffffff !important;
        border-color: #777 !important;
    }

    /* Botão da página ATUAL (Ativa) */
    .pagination .page-item.active .page-link {
        background-color: transparent !important;
        color: #ffffff !important;
        border-color: #ffffff !important;
        font-weight: bold;
    }

    /* Botões desativados (ex: "Anterior" na página 1) */
    .pagination .page-item.disabled .page-link {
        background-color: transparent !important;
        color: #777 !important;
        border-color: #2c2c2c !important;
        cursor: not-allowed;
    }

    /* Remove o contorno azul do Bootstrap ao focar/clicar */
    .pagination .page-link:focus {
        box-shadow: none !important;
        background-color: transparent !important;
        color: #fff !important;
    }

    /* --- ESTILOS ADICIONAIS PORTFÓLIO --- */
    .filtro-item {
        text-align: left;
    }

    .btn-square-filtro {
        width: 36px !important;
        height: 36px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        padding: 0 !important;
    }
</style>

<main>
    <div class="container text-center my-5 py-5">

        <h2 class="mb-5">GALERIA DE TRABALHOS</h2>

        <form class="filtro-container mb-5 d-flex flex-wrap gap-2 align-items-end" method="GET">

            <div class="filtro-item flex-grow-1">
                <label class="form-label small mb-1">Título:</label>
                <input type="text" class="form-control form-control-sm" name="titulo" value="<?php echo htmlspecialchars($titulo_filtro ?? ''); ?>">
            </div>

            <div class="filtro-item flex-grow-1">
                <label class="form-label small mb-1">Estilo:</label>
                <select name="estilo" class="form-select form-select-sm bg-dark text-light border-secondary" style="background-color: #2c2c2c !important;">
                    <option value="todos">Todos</option>
                    <?php if (isset($lista_estilos)): ?>
                        <?php foreach ($lista_estilos as $est): ?>
                            <option value="<?= htmlspecialchars($est['nome']) ?>" <?= (isset($estilo_filtro) && $estilo_filtro == $est['nome']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($est['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="filtro-item flex-grow-1">
                <label class="form-label small mb-1">Local do Corpo:</label>
                <input type="text" class="form-control form-control-sm" name="local_corpo" value="<?php echo htmlspecialchars($local_filtro ?? ''); ?>">
            </div>

            <div class="filtro-item" style="width: 70px;">
                <label class="form-label small mb-1">Sessões:</label>
                <input type="number" class="form-control form-control-sm" name="qtd_sessoes" min="1" value="<?php echo htmlspecialchars($sessoes_filtro ?? ''); ?>">
            </div>

            <div class="filtro-item">
                <label class="form-label small mb-1">Data Publicação:</label>
                <input type="date" class="form-control form-control-sm" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio ?? ''); ?>">
            </div>

            <div class="filtro-item">
                <label class="form-label small mb-1"> </label>
                <input type="date" class="form-control form-control-sm" name="data_fim" value="<?php echo htmlspecialchars($data_fim ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-sm btn-primary btn-square-filtro" title="Aplicar Filtros">
                <i class="bi bi-check-lg"></i>
            </button>
            <a href="portfolio.php" class="btn btn-sm btn-outline-secondary btn-square-filtro" title="Limpar Filtros">
                <i class="bi bi-x-lg"></i>
            </a>

            <div class="d-flex gap-2 align-items-end ms-auto">
                <input type="hidden" name="ordem" id="input-ordem" value="<?php echo htmlspecialchars($ordem ?? 'desc'); ?>">

                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-light btn-square-filtro" type="button" data-bs-toggle="dropdown" title="Ordenar">
                        <i class="bi bi-sort-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a href="#" class="dropdown-item <?php if (!isset($ordem) || $ordem == 'desc') echo 'active'; ?>" onclick="document.getElementById('input-ordem').value='desc'; this.closest('form').submit(); return false;">Mais Recentes</a></li>
                        <li><a href="#" class="dropdown-item <?php if (isset($ordem) && $ordem == 'asc') echo 'active'; ?>" onclick="document.getElementById('input-ordem').value='asc'; this.closest('form').submit(); return false;">Mais Antigas</a></li>
                    </ul>
                </div>
            </div>
        </form>

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

        <?php
        $pagina_atual = $pagina_atual ?? 1;

        if (isset($total_paginas) && $total_paginas >= 1):
            // Gera a URL dinâmica pegando todos os filtros atuais para não perder a pesquisa ao mudar de página
            $query_params = $_GET;
            unset($query_params['pagina']); // Tira a página atual para substituir pela nova
            $base_url = "?" . http_build_query($query_params) . (!empty($query_params) ? "&" : "") . "pagina=";
        ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">

                    <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $base_url . ($pagina_atual - 1) ?>">Anterior</a>
                    </li>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= ($pagina_atual == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $base_url . $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $base_url . ($pagina_atual + 1) ?>">Próximo</a>
                    </li>

                </ul>
            </nav>
        <?php endif; ?>

    </div>
</main>

<?php include '../includes/footer.php'; ?>