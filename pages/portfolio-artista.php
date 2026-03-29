<?php
session_start();
// se não for artista, volta para o portfolio público
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: portfolio.php");
    exit();
}

$trabalhos = $trabalhos ?? [];
$lista_estilos = $lista_estilos ?? [];
$total_paginas = $total_paginas ?? 0;
$pagina_atual = $pagina_atual ?? 1;

$titulo_pagina = "Gerenciar Portfólio";
include '../includes/header.php';
include '../actions/a.portfolio.php';
?>

<?php

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

<style>
    .pagination .page-link {
        background-color: transparent !important;
        border: 1px solid #2c2c2c !important;
        color: #aaa !important;
        margin: 0 4px;
        border-radius: 6px !important;
        transition: all 0.3s ease;
    }

    .pagination .page-link:hover {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #ffffff !important;
        border-color: #777 !important;
    }

    .pagination .page-item.active .page-link {
        background-color: transparent !important;
        color: #ffffff !important;
        border-color: #ffffff !important;
        font-weight: bold;
    }

    .pagination .page-item.disabled .page-link {
        background-color: transparent !important;
        color: #777 !important;
        border-color: #2c2c2c !important;
        cursor: not-allowed;
    }

    .pagination .page-link:focus {
        box-shadow: none !important;
        background-color: transparent !important;
        color: #fff !important;
    }

    .btn-primary {
        background-color: #333 !important;
        border-color: #444 !important;
        color: #fff !important;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #444 !important;
        border-color: #555 !important;
    }

    .btn-primary:focus,
    .btn-primary:active,
    .btn-primary:focus-visible,
    .show>.btn-primary.dropdown-toggle {
        background-color: #222 !important;
        border-color: #ffffff !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .btn-portfolio-action {
        background-color: transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
        width: 120px;
        box-sizing: border-box;
    }

    .btn-portfolio-action i {
        font-size: 1.2rem;
        margin-bottom: 4px;
    }

    .btn-portfolio-action span {
        font-size: 0.85rem;
        font-weight: 500;
    }

    .btn-action-editar {
        border: 1px solid #bdbdbd;
        color: #d8d8d8;
        background-color: transparent;
        transition: all 0.3s ease;
    }

    .btn-action-editar:hover {
        background-color: rgba(255, 255, 255, 0.05);
        border-color: #ffffff;
        color: #ffffff;
    }

    .btn-action-editar:focus,
    .btn-action-editar:active {
        box-shadow: none !important;
        outline: none !important;
        border-color: #ffffff !important;
    }

    .btn-action-excluir {
        border: 1px solid #dc3545;
        color: #dc3545;
    }

    .btn-action-excluir:hover {
        background-color: rgba(220, 53, 69, 0.1);
        border-color: #dc3545;
        color: #dc3545;
    }
</style>

<main>
    <div class="container my-5 py-5">
        <div class="text-center mb-5">
            <button class="btn btn-outline-light px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalPortfolio">
                <i class="bi bi-plus me-2"></i>ADICIONAR NOVO TRABALHO
            </button>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success text-center alert-dismissible fade show mt-3 mx-auto" style="max-width: 600px;" role="alert">
                    Ação realizada com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-danger text-center alert-dismissible fade show mt-3 mx-auto" style="max-width: 600px;" role="alert">
                    Erro ao processar a requisição. Tente novamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <?php if (count($trabalhos) > 0): ?>
                <?php foreach ($trabalhos as $item): ?>
                    <div class="col-lg-3 col-md-4 col-6 mb-4">
                        <div class="portfolio-item">
                            <img src="../imagens/portfolio/<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="img-fluid">

                            <div class="portfolio-detalhes-overlay">
                                <h5 class="detalhes-titulo"><?= htmlspecialchars($item['titulo']) ?></h5>
                                <p class="detalhes-info">Estilo: <?= $item['estilo_nome'] ?></p>
                                <p class="detalhes-info">Tempo: <?= $item['tempo_execucao'] ?></p>
                                <p class="detalhes-info">Sessões: <?= $item['qtd_sessoes'] ?></p>
                                <p class="detalhes-info">Local: <?= $item['local_corpo'] ?></p>

                                <div class="mt-3 d-flex gap-2 justify-content-center">
                                    <a href="#"
                                        class="btn-portfolio-action btn-action-editar"
                                        title="Editar Trabalho"
                                        data-id="<?= $item['id_portfolio'] ?>"
                                        data-titulo="<?= htmlspecialchars($item['titulo']) ?>"
                                        data-estilo="<?= $item['id_estilo'] ?>"
                                        data-tempo="<?= $item['tempo_execucao'] ?>"
                                        data-sessoes="<?= $item['qtd_sessoes'] ?>"
                                        data-local="<?= htmlspecialchars($item['local_corpo'] ?? '') ?>"
                                        data-desc="<?= htmlspecialchars($item['descricao'] ?? '') ?>"
                                        data-img="<?= $item['imagem'] ?>">
                                        <i class="bi bi-pencil"></i>
                                        <span>Editar</span>
                                    </a>

                                    <button type="button"
                                        class="btn-portfolio-action btn-action-excluir btn-excluir-js"
                                        data-id="<?= $item['id_portfolio'] ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalExcluir"
                                        title="Excluir Trabalho">
                                        <i class="bi bi-trash"></i>
                                        <span>Excluir</span>
                                    </button>
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

        <?php $total_paginas_display = max(1, $total_paginas); ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($pagina_atual <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina_atual - 1 ?>">Anterior</a>
                </li>
                <?php for ($i = 1; $i <= $total_paginas_display; $i++): ?>
                    <li class="page-item <?= ($pagina_atual == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($pagina_atual >= $total_paginas_display) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina_atual + 1 ?>">Próximo</a>
                </li>
            </ul>
        </nav>
    </div>
</main>

<div class="modal fade" id="modalPortfolio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="../actions/a.novo-portfolio.php" method="POST" enctype="multipart/form-data" class="modal-content bg-dark text-light border-secondary">

            <div class="modal-header border-bottom border-secondary py-2">
                <h5 class="modal-title fs-5">Adicionar Novo Trabalho</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                <div class="row g-2">
                    <div class="col-12 mb-1">
                        <label for="imagem" class="form-label small text-white-50">Imagem:</label>
                        <input type="file" name="imagem" class="form-control bg-dark text-light border-secondary" id="imagem" accept="image/*" required>
                    </div>

                    <div class="col-12 mb-1">
                        <label for="titulo" class="form-label small text-white-50">Título:</label>
                        <input type="text" name="titulo" class="form-control bg-dark text-light border-secondary" id="titulo" required>
                    </div>

                    <div class="col-12 mb-1">
                        <label class="form-label small text-white-50">Estilo:</label>
                        <select name="id_estilo" class="form-select bg-dark text-light border-secondary" required>
                            <option value=""></option>
                            <?php foreach ($lista_estilos as $est): ?>
                                <option value="<?= $est['id_estilo'] ?>"><?= $est['nome'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6 mb-1">
                        <label for="tempo_execucao" class="form-label small text-white-50">Tempo:</label>
                        <input type="number" name="tempo_execucao" class="form-control bg-dark text-light border-secondary" id="tempo_execucao" min="1">
                    </div>
                    <div class="col-6 mb-1">
                        <label for="qtd_sessoes" class="form-label small text-white-50">Sessões:</label>
                        <input type="number" name="qtd_sessoes" class="form-control bg-dark text-light border-secondary" id="qtd_sessoes" min="1">
                    </div>

                    <div class="col-12 mb-1">
                        <label for="local_corpo" class="form-label small text-white-50">Local do Corpo:</label>
                        <input type="text" name="local_corpo" class="form-control bg-dark text-light border-secondary" id="local_corpo">
                    </div>

                    <div class="col-12">
                        <label for="descricao" class="form-label small text-white-50">Descrição (Opcional):</label>
                        <textarea name="descricao" class="form-control bg-dark text-light border-secondary" rows="2" style="resize: none;"></textarea>
                    </div>

                </div>
            </div>

            <div class="modal-footer border-top border-secondary py-2"> <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-outline-light btn-sm px-4">Salvar Trabalho</button>
            </div>

        </form>
    </div>
</div>

<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Excluir Trabalho</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-0 text-white-50">Tem certeza que deseja excluir esta foto do seu portfólio?<br><strong>Esta ação não poderá ser desfeita.</strong></p>
            </div>
            <div class="modal-footer border-top border-secondary justify-content-center gap-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btnConfirmarExclusao" class="btn btn-danger">Excluir</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarPortfolio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="../actions/a.editar-portfolio.php" method="POST" enctype="multipart/form-data" class="modal-content bg-dark text-light border-secondary">

            <div class="modal-header border-bottom border-secondary py-2 px-3">
                <h5 class="modal-title fs-6">Editar Trabalho</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-2 px-3">
                <input type="hidden" name="id_portfolio" id="edit_id">

                <div class="row g-2">
                    <div class="col-12 d-flex align-items-center gap-3 mb-2 p-2 rounded" style="background-color: rgba(255,255,255,0.05);">
                        <img src="" id="edit_preview" class="img-thumbnail bg-dark border-secondary" style="max-height: 60px; width: 60px; object-fit: cover;">
                        <div>
                            <label class="form-label small mb-0" style="color: #aaa;">Alterar Imagem:</label>
                            <input type="file" name="imagem" class="form-control form-control-sm bg-dark text-light border-secondary" accept="image/*">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label mb-0" style="font-size: 0.8rem; color: #aaa;">Título:</label>
                        <input type="text" name="titulo" id="edit_titulo" class="form-control bg-dark text-light border-secondary" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label mb-0" style="font-size: 0.8rem; color: #aaa;">Estilo:</label>
                        <select name="id_estilo" id="edit_estilo" class="form-select bg-dark text-light border-secondary" required>
                            <?php foreach ($lista_estilos as $est): ?>
                                <option value="<?= $est['id_estilo'] ?>"><?= $est['nome'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-6">
                        <label class="form-label mb-0" style="font-size: 0.8rem; color: #aaa;">Tempo (h):</label>
                        <input type="number" name="tempo_execucao" id="edit_tempo" class="form-control bg-dark text-light border-secondary">
                    </div>
                    <div class="col-6">
                        <label class="form-label mb-0" style="font-size: 0.8rem; color: #aaa;">Sessões:</label>
                        <input type="number" name="qtd_sessoes" id="edit_sessoes" class="form-control bg-dark text-light border-secondary">
                    </div>

                    <div class="col-12">
                        <label class="form-label mb-0" style="font-size: 0.8rem; color: #aaa;">Local do Corpo:</label>
                        <input type="text" name="local_corpo" id="edit_local" class="form-control bg-dark text-light border-secondary">
                    </div>

                    <div class="col-12">
                        <label class="form-label mb-0" style="font-size: 0.8rem; color: #aaa;">Descrição:</label>
                        <textarea name="descricao" id="edit_descricao" class="form-control bg-dark text-light border-secondary" rows="2" style="resize: none;"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top border-secondary py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-outline-light px-4">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica de Exclusão (que você já tinha)
        document.querySelectorAll('.btn-excluir-js').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                document.getElementById('btnConfirmarExclusao').href = '../actions/a.excluir-portfolio.php?id=' + id;
            });
        });

        // NOVA Lógica de Edição (Passando os dados para o Modal)
        document.querySelectorAll('.btn-action-editar').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // Impede de navegar para a página not found

                // Aqui vamos pegar os dados direto dos atributos que vamos colocar no botão
                document.getElementById('edit_id').value = this.getAttribute('data-id');
                document.getElementById('edit_titulo').value = this.getAttribute('data-titulo');
                document.getElementById('edit_estilo').value = this.getAttribute('data-estilo');
                // O parseInt extrai apenas o número (ex: de "6 horas" vira 6)
                let tempoValor = this.getAttribute('data-tempo');
                document.getElementById('edit_tempo').value = parseInt(tempoValor) || "";
                document.getElementById('edit_sessoes').value = this.getAttribute('data-sessoes');
                document.getElementById('edit_local').value = this.getAttribute('data-local');
                document.getElementById('edit_descricao').value = this.getAttribute('data-desc');
                document.getElementById('edit_preview').src = '../imagens/portfolio/' + this.getAttribute('data-img');

                // Abre o modal manualmente
                var modalEdit = new bootstrap.Modal(document.getElementById('modalEditarPortfolio'));
                modalEdit.show();
            });
        });
    });
</script>



<?php include '../includes/footer.php'; ?>