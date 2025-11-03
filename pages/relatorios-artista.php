<?php
session_start();

$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "artista";

$titulo_pagina = "Relatórios";
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
        <h2 class="text-center mb-5">RELATÓRIOS</h2>

        <ul class="nav nav-tabs nav-tabs-dark mb-4" id="abasRelatorios" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button" role="tab" aria-controls="tab-historico" aria-selected="true">Histórico de Agendamentos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clientes-tab" data-bs-toggle="tab" data-bs-target="#tab-clientes" type="button" role="tab" aria-controls="tab-clientes" aria-selected="false">Clientes Cadastrados</button>
            </li>
        </ul>

        <div class="tab-content" id="abasRelatoriosConteudo">

            <div class="tab-pane fade show active" id="tab-historico" role="tabpanel" aria-labelledby="historico-tab">
                <h4 class="mb-4">Histórico de Agendamentos</h4>

                <div class="mb-5">
                    <div class="d-flex justify-content-end mb-3">
                        <a href="#" class="btn btn-sm btn-outline-light">Este Mês</a>
                        <a href="#" class="btn btn-sm btn-outline-light ms-2">Mês Passado</a>
                        <a href="#" class="btn btn-sm btn-outline-light ms-2">Todos</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">Data</th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Projeto</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>20/09/2025</td>
                                    <td>Thábata Ricci</td>
                                    <td>Fechamento de Braço (Sessão 1/3)</td>
                                    <td><span class="badge status-concluido">Concluído</span></td>
                                </tr>
                                <tr>
                                    <td>10/08/2025</td>
                                    <td>Izabella Bianca</td>
                                    <td>Tatuagem Geométrica (Sessão 1/1)</td>
                                    <td><span class="badge status-cancelado">Cancelado</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-clientes" role="tabpanel" aria-labelledby="clientes-tab">
                <h4 class="mb-4">Clientes Cadastrados</h4>

                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th scope="col">Nome</th>
                                <th scope="col">E-mail</th>
                                <th scope="col">Telefone</th>
                                <th scope="col">Data de Cadastro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Nome Completo do Cliente 1</td>
                                <td>cliente1@email.com</td>
                                <td>(19) 99999-1111</td>
                                <td>20/08/2025</td>
                            </tr>
                            <tr>
                                <td>Nome Completo do Cliente 2</td>
                                <td>cliente2@email.com</td>
                                <td>(19) 99999-2222</td>
                                <td>18/08/2025</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>