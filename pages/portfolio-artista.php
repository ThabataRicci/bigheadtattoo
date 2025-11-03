<?php
session_start();

$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "artista";

$titulo_pagina = "Gerenciar Portfólio";
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
        <div class="text-center mb-5">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPortfolio">
                <i class="bi bi-plus-lg me-2"></i>ADICIONAR NOVO TRABALHO
            </button>
        </div>

        <div class="row">

            <div class="col-lg-3 col-md-4 col-6 mb-4">
                <div class="portfolio-item">
                    <img src="../imagens/exemplo1.jpg" alt="Tatuagem de Dragão">
                    <div class="portfolio-detalhes-overlay">
                        <h5 class="detalhes-titulo">Dragão</h5>
                        <p class="detalhes-info">Estilo: Oriental</p>
                        <p class="detalhes-info">Tempo: 6 horas</p>
                        <p class="detalhes-info">Local: Costas</p>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#modalPortfolio">Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este trabalho?');">Excluir</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-6 mb-4">
                <div class="portfolio-item">
                    <img src="../imagens/exemplo2.jpg" alt="Tatuagem de Rosa">
                    <div class="portfolio-detalhes-overlay">
                        <h5 class="detalhes-titulo">Rosa</h5>
                        <p class="detalhes-info">Estilo: Fineline</p>
                        <p class="detalhes-info">Tempo: 6 horas</p>
                        <p class="detalhes-info">Local: Braço</p>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#modalPortfolio">Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este trabalho?');">Excluir</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled"><a class="page-link" href="#">Anterior</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">Próximo</a></li>
            </ul>
        </nav>
    </div>
</main>

<div class="modal fade" id="modalPortfolio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Novo Trabalho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form onsubmit="alert('Trabalho salvo com sucesso! (Simulação)'); return false;">
                    <div class="mb-3">
                        <label for="foto" class="form-label">Foto do Trabalho:</label>
                        <input type="file" class="form-control" id="foto" required>
                    </div>
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título/Descrição:</label>
                        <input type="text" class="form-control" id="titulo" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estilos (selecione um ou mais):</label>
                        <div class="formulario-container p-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="estilo_fineline" value="fineline">
                                <label class="form-check-label" for="estilo_fineline">Fineline</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="estilo_oriental" value="oriental">
                                <label class="form-check-label" for="estilo_oriental">Oriental</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="estilo_geometrico" value="geometrico">
                                <label class="form-check-label" for="estilo_geometrico">Geométrico</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="estilo_maori" value="maori">
                                <label class="form-check-label" for="estilo_maori">Maori</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="estilo_escrita" value="escrita">
                                <label class="form-check-label" for="estilo_escrita">Escrita</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="tempo" class="form-label">Tempo de Execução (em horas):</label>
                        <input type="number" class="form-control" id="tempo">
                    </div>
                    <div class="mb-3">
                        <label for="local" class="form-label">Local do Corpo:</label>
                        <input type="text" class="form-control" id="local">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Trabalho</button>
            </div>
        </div>
    </div>
</div>


<?php
include '../includes/footer.php';
?>