<?php
session_start();
// se não for artista, volta para o portfolio público
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: portfolio.php");
    exit();
}

$titulo_pagina = "Gerenciar Portfólio";
include '../includes/header.php';
include '../actions/a.portfolio.php';
?>

<?php
// Menu do Painel
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    echo '<div class="submenu-painel">';
    echo '<a href="dashboard-artista.php" class="' . ($pagina_ativa == 'dashboard-artista.php' ? 'active' : '') . '">Início</a>';
    echo '<a href="agenda.php" class="' . ($pagina_ativa == 'agenda.php' ? 'active' : '') . '">Agenda</a>';
    echo '<a href="portfolio-artista.php" class="' . ($pagina_ativa == 'portfolio-artista.php' ? 'active' : '') . '">Portfólio</a>';
    echo '<a href="relatorios-artista.php" class="' . ($pagina_ativa == 'relatorios-artista.php' ? 'active' : '') . '">Relatórios</a>';
    echo '<a href="configuracoes-artista.php" class="' . ($pagina_ativa == 'configuracoes-artista.php' ? 'active' : '') . '">Configurações</a>';
    echo '</div>';
}
?>

<main>
    <div class="container my-5 py-5">
        <div class="text-center mb-5">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPortfolio">
                <i class="bi bi-plus-lg me-2"></i>ADICIONAR NOVO TRABALHO
            </button>
        </div>

        <div class="row">
            <?php if (count($trabalhos) > 0): ?>
                <?php foreach ($trabalhos as $item): ?>
                    <div class="col-lg-3 col-md-4 col-6 mb-4">
                        <div class="portfolio-item">
                            <img src="../imagens/portfolio/<?= $item['imagem'] ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="img-fluid">

                            <div class="portfolio-detalhes-overlay">
                                <h5 class="detalhes-titulo"><?= htmlspecialchars($item['titulo']) ?></h5>
                                <p class="detalhes-info">Estilo: <?= $item['estilo_nome'] ?></p>
                                <p class="detalhes-info">Tempo: <?= $item['tempo_execucao'] ?></p>
                                <p class="detalhes-info">Sessões: <?= $item['qtd_sessoes'] ?></p>
                                <p class="detalhes-info">Local: <?= $item['local_corpo'] ?></p>

                                <div class="mt-3">
                                    <a href="../actions/a.excluir-portfolio.php?id=<?= $item['id_portfolio'] ?>"
                                        class="btn btn-sm btn-danger w-100"
                                        onclick="return confirm('Deseja realmente excluir este trabalho?')">
                                        <i class="bi bi-trash"></i> Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-white">Nenhum trabalho cadastrado no seu portfólio.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_paginas > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_atual - 1 ?>">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= ($pagina_atual == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($pagina_atual >= $total_paginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_atual + 1 ?>">Próximo</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</main>

<div class="modal fade" id="modalPortfolio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered text-dark">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Novo Trabalho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../actions/a.novo-portfolio.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="imagem" class="form-label">Foto do Trabalho:</label>
                        <input type="file" name="imagem" class="form-control" id="imagem" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título/Descrição Curta:</label>
                        <input type="text" name="titulo" class="form-control" id="titulo" placeholder="Ex: Dragão Realista" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estilo Principal:</label>
                        <select name="id_estilo" class="form-select" required>
                            <option value="">Selecione um estilo...</option>
                            <?php foreach ($lista_estilos as $est): ?>
                                <option value="<?= $est['id_estilo'] ?>"><?= $est['nome'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tempo_execucao" class="form-label">Tempo (ex: 4h):</label>
                            <input type="text" name="tempo_execucao" class="form-control" id="tempo_execucao">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="qtd_sessoes" class="form-label">Sessões:</label>
                            <input type="number" name="qtd_sessoes" class="form-control" id="qtd_sessoes" min="1" value="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="local_corpo" class="form-label">Local do Corpo:</label>
                        <input type="text" name="local_corpo" class="form-control" id="local_corpo" placeholder="Ex: Antebraço">
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição Detalhada:</label>
                        <textarea name="descricao" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Trabalho</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>