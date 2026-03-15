<?php
session_start();

$usuario_logado = false;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['usuario_perfil'] == 'artista') {
        header("location: dashboard-artista.php");
        exit;
    }
    $usuario_logado = true;
}

$titulo_pagina = "Solicitar Orçamento";
include '../includes/header.php';
?>

<?php
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {

    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    $link_prefix = '';

    echo '<div class="submenu-painel">';

    if ($_SESSION['usuario_perfil'] == 'artista') {
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
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">

                <?php if ($usuario_logado): ?>

                    <?php if (isset($_GET['sucesso'])): ?>
                        <div class="alert alert-success text-center mb-4">Solicitação enviada com sucesso! O artista analisará sua ideia.</div>
                    <?php endif; ?>

                    <?php if (isset($_GET['erro'])): ?>
                        <div class="alert alert-danger text-center mb-4">Erro ao enviar solicitação. Tente novamente.</div>
                    <?php endif; ?>

                    <form class="formulario-container text-center" action="../actions/a.solicitacao-orcamento.php" method="POST" enctype="multipart/form-data">

                        <h2 class="mb-3">SOLICITAR ORÇAMENTO</h2>
                        <p class="text-white-50 mb-4">Descreva sua tatuagem e envie referências. O artista irá analisar o projeto e você receberá a notificação para agendar sua sessão.</p>

                        <div class="text-start">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="local_corpo" class="form-label">Local do Corpo:</label>
                                    <input type="text" class="form-control" id="local_corpo" name="local_corpo" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tamanho_aproximado" class="form-label d-flex justify-content-between align-items-center">
                                        Tamanho Aproximado:
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#modalTamanhos" class="text-info small text-decoration-none">
                                            <i class="bi bi-info-circle"></i>
                                        </a>
                                    </label>
                                    <select class="form-select" id="tamanho_aproximado" name="tamanho_aproximado" required>
                                        <option value="" selected disabled>Selecione o tamanho...</option>
                                        <option value="Pequeno (até 10cm)">Pequeno (até 10cm)</option>
                                        <option value="Médio (11cm a 20cm)">Médio (11cm a 20cm)</option>
                                        <option value="Grande (acima de 20cm)">Grande (acima de 20cm)</option>
                                        <option value="Fechamento">Fechamento</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descricao_ideia" class="form-label">Detalhes da Ideia/Desenho:</label>
                                <textarea class="form-control" id="descricao_ideia" name="descricao_ideia" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="referencia_ideia" class="form-label">Imagem de Referência (Opcional):</label>
                                <input type="file" class="form-control" id="referencia_ideia" name="referencia_ideia">
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">ENVIAR IDEIA PARA ANÁLISE</button>
                        </div>
                    </form>

                <?php else: ?>
                    <div class="formulario-container text-center">
                        <h2 class="mb-3">SOLICITAR ORÇAMENTO</h2>
                        <p class="text-white-50 mb-4">Para solicitar um orçamento, você precisa estar conectado.</p>

                        <div class="d-grid gap-3">
                            <a href="login.php?redirect=solicitar-orcamento.php" class="btn btn-primary">FAZER LOGIN</a>

                            <a href="cadastro.php?redirect=solicitar-orcamento.php" class="btn btn-outline-light">CRIAR CONTA</a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>


    <div class="modal fade" id="modalTamanhos" tabindex="-1" aria-labelledby="modalTamanhosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content text-dark p-2 bg-dark">
                <div class="modal-header bg-dark border-bottom border-secondary text-white">
                    <h5 class="modal-title" id="modalTamanhosLabel">Guia de Tamanhos de Tatuagem</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-dark text-white-50">
                    <p class="mb-4 text-center">Use esta referência para ter uma noção do tamanho da sua ideia:</p>

                    <div class="row g-4 text-center">
                        <div class="col-md-3 col-6">
                            <div class="p-3 border border-secondary rounded h-100">
                                <i class="bi bi-vinyl-fill text-white fs-1"></i>
                                <h6 class="text-white mt-3">Pequeno</h6>
                                <p class="small mb-2">Até 10cm</p>
                                <span class="badge bg-secondary text-wrap py-1 px-2" style="font-size: 11px;">Ex: Moeda, Cartão</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 border border-secondary rounded h-100">
                                <i class="bi bi-phone text-white fs-1"></i>
                                <h6 class="text-white mt-3">Médio</h6>
                                <p class="small mb-2">11 a 20cm</p>
                                <span class="badge bg-secondary text-wrap py-1 px-2" style="font-size: 11px;">Ex: Celular, Mão</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 border border-secondary rounded h-100">
                                <i class="bi bi-book text-white fs-1"></i>
                                <h6 class="text-white mt-3">Grande</h6>
                                <p class="small mb-2">Acima de 20cm</p>
                                <span class="badge bg-secondary text-wrap py-1 px-2" style="font-size: 11px;">Ex: Caderno, Metade das costas</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 border border-secondary rounded h-100">
                                <i class="bi bi-person-arms-up text-white fs-1"></i>
                                <h6 class="text-white mt-3">Fechamento</h6>
                                <p class="small mb-2">Área Completa</p>
                                <span class="badge bg-secondary text-start w-100 py-1 px-2 text-wrap" style="font-size: 11px;">Ex: Braço inteiro, Perna, Costas fechadas</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 mb-0 text-center small p-3 bg-dark border border-secondary text-white-50">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                        Esta é apenas uma estimativa. O artista avaliará o tamanho exato de acordo com a anatomia e os detalhes do desenho.
                    </div>
                </div>
                <div class="modal-footer bg-dark border-top border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Entendi</button>
                </div>
            </div>
        </div>
    </div>

</main>

<?php
include '../includes/footer.php';
?>