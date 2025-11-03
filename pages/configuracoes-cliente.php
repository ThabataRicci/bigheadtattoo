<?php
session_start();
$titulo_pagina = "Configurações";
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

                <h2 class="text-center mb-5">EDITAR MEU PERFIL</h2>

                <form class="formulario-container">

                    <ul class="nav nav-tabs nav-tabs-dark mb-4" id="abasConfigCliente" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#tab-dados" type="button" role="tab" aria-controls="tab-dados" aria-selected="true">Dados Pessoais</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="senha-tab" data-bs-toggle="tab" data-bs-target="#tab-senha" type="button" role="tab" aria-controls="tab-senha" aria-selected="false">Alterar Senha</button>
                        </li>
                    </ul>

                    <div class="tab-content tab-content-boxed" id="abasConfigClienteConteudo">

                        <div class="tab-pane fade show active" id="tab-dados" role="tabpanel" aria-labelledby="dados-tab">
                            <h5 class="text-white-50 mb-3">DADOS PESSOAIS</h5>
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo:</label>
                                <input type="text" class="form-control" id="nome" value="Thábata Ricci">
                            </div>
                            <div class="mb-4">
                                <label for="telefone" class="form-label">Telefone:</label>
                                <input type="tel" class="form-control" id="telefone" value="(19) 99999-2222">
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-senha" role="tabpanel" aria-labelledby="senha-tab">
                            <h5 class="text-white-50 mb-3">ALTERAR SENHA</h5>
                            <div class="mb-3">
                                <label for="senha-atual" class="form-label">Senha Atual:</label>
                                <input type="password" class="form-control" id="senha-atual">
                            </div>
                            <div class="mb-3">
                                <label for="nova-senha" class="form-label">Nova Senha:</label>
                                <input type="password" class="form-control" id="nova-senha">
                            </div>
                            <div class="mb-4">
                                <label for="confirmar-nova-senha" class="form-label">Confirmar Nova Senha:</label>
                                <input type="password" class="form-control" id="confirmar-nova-senha">
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>