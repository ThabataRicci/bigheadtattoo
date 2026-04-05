<?php
session_start();
$titulo_pagina = "Portfólio";
include '../includes/header.php';
// A action abaixo carrega as variáveis: $trabalhos, $lista_estilos, $estilo_selecionado, $total_paginas e $pagina_atual
include '../actions/a.portfolio.php';
?>

<?php
// Menu de navegação interna (Exibe apenas se estiver logado)
if (isset($_SESSION['usuario_id'])) {
    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    echo '<div class="submenu-painel">';
    if ($_SESSION['usuario_perfil'] == 'artista') {
        echo '<a href="dashboard-artista.php">Início</a>';
        echo '<a href="agenda.php">Agenda</a>';
        echo '<a href="portfolio-artista.php" class="active">Portfólio</a>';
        echo '<a href="relatorios-artista.php">Relatórios</a>';
    } else {
        echo '<a href="dashboard-cliente.php">Início</a>';
        echo '<a href="agendamentos-cliente.php">Meus Agendamentos</a>';
        echo '<a href="solicitar-orcamento.php">Orçamento</a>';
    }
    echo '</div>';
}
?>

<main>
    <div class="container text-center my-5 py-5">
        <h2 class="mb-5">GALERIA DE TRABALHOS</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'excluido'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Trabalho removido com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="filtros-portfolio mb-5">
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle" type="button" id="botaoFiltro" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-filter me-2"></i>
                    <?= ($estilo_selecionado === 'todos') ? 'FILTRAR POR ESTILO' : 'ESTILO: ' . strtoupper(htmlspecialchars($estilo_selecionado)) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark shadow" aria-labelledby="botaoFiltro">
                    <li><a class="dropdown-item" href="?estilo=todos">Todos os Estilos</a></li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    
                    <?php if (!empty($lista_estilos)): ?>
                        <?php foreach($lista_estilos as $est): ?>
                            <li>
                                <a class="dropdown-item" href="?estilo=<?= urlencode($est['nome']) ?>">
                                    <?= htmlspecialchars($est['nome']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><span class="dropdown-item disabled">Nenhum estilo cadastrado</span></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="row">
            <?php if(!empty($trabalhos)): ?>
                <?php foreach($trabalhos as $item): ?>
                    <div class="col-lg-3 col-md-4 col-6 mb-4">
                        <div class="portfolio-item">
                            <img src="../imagens/portfolio/<?= $item['imagem'] ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="img-fluid">
                            <div class="portfolio-detalhes-overlay">
                                <h5 class="detalhes-titulo"><?= htmlspecialchars($item['titulo']) ?></h5>
                                <p class="detalhes-info">Estilo: <?= $item['estilo_nome'] ?></p>
                                <p class="detalhes-info">Local: <?= $item['local_corpo'] ?></p>
                                
                                <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'artista'): ?>
                                    <hr class="border-light w-75">
                                    <a href="../actions/a.excluir-portfolio.php?id=<?= $item['id_portfolio'] ?>" 
                                       class="btn btn-danger btn-sm w-100" 
                                       onclick="return confirm('Deseja excluir este trabalho?')">
                                       <i class="bi bi-trash me-1"></i> Excluir
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 py-5">
                    <p class="text-white-50">Nenhum trabalho encontrado para este estilo no momento.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if($total_paginas > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina_atual - 1 ?>&estilo=<?= urlencode($estilo_selecionado) ?>">Anterior</a>
                </li>
                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= ($pagina_atual == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i ?>&estilo=<?= urlencode($estilo_selecionado) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina_atual + 1 ?>&estilo=<?= urlencode($estilo_selecionado) ?>">Próximo</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>