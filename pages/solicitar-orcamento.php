<?php
session_start();

$usuario_logado = false;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['user_role'] === 'artista') {
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
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">

                <?php if ($usuario_logado): ?>
                    <form class="formulario-container text-center" action="processa_solicitacao.php" method="POST" enctype="multipart/form-data">

                        <h2 class="mb-3">SOLICITAR ORÇAMENTO</h2>
                        <p class="text-white-50 mb-4">Descreva sua tatuagem e envie referências. O artista irá analisar o projeto e você receberá a notificação para agendar sua sessão.</p>

                        <div class="text-start">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="local_corpo" class="form-label">Local do Corpo:</label>
                                    <input type="text" class="form-control" id="local_corpo" name="local_corpo" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tamanho_aproximado" class="form-label">Tamanho Aproximado:</label>
                                    <select class="form-select" id="tamanho_aproximado" name="tamanho_aproximado" required>
                                        <option value="" selected disabled>Selecione o tamanho...</option>
                                        <option value="Pequeno (até 10cm)">Pequeno (até 10cm)</option>
                                        <option value="Médio (11cm a 20cm)">Médio (11cm a 20cm)</option>
                                        <option value="Grande (acima de 20cm)">Grande (acima de 20cm)</option>
                                        <option value="Fechamento">Fechamento (braço, perna, etc.)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descricao_ideia" class="form-label">Detalhes da Ideia / Desenho:</label>
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
                            <a href="login.php" class="btn btn-primary">FAZER LOGIN</a>
                            <a href="cadastro.php" class="btn btn-outline-light">CRIAR CONTA</a>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>