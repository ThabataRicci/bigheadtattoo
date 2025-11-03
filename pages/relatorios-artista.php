<?php
session_start();

// Simula o login do artista para teste
$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "artista";

$titulo_pagina = "Relatórios";
include '../includes/header.php';
?>

<?php
// Define qual página está ativa para destacar o link no menu
$pagina_ativa = basename($_SERVER['PHP_SELF']);
?>

<div class="submenu-painel">
    <a href="dashboard-artista.php" class="<?php echo ($pagina_ativa == 'dashboard-artista.php') ? 'active' : ''; ?>">Início</a>
    <a href="agenda-artista.php" class="<?php echo ($pagina_ativa == 'agenda-artista.php' || $pagina_ativa == 'agenda.php') ? 'active' : ''; ?>">Agenda</a>
    <a href="portfolio-artista.php" class="<?php echo ($pagina_ativa == 'portfolio-artista.php') ? 'active' : ''; ?>">Portfólio</a>
    <a href="relatorios-artista.php" class="<?php echo ($pagina_ativa == 'relatorios-artista.php') ? 'active' : ''; ?>">Relatórios</a>
    <a href="configuracoes-artista.php" class="<?php echo ($pagina_ativa == 'configuracoes-artista.php') ? 'active' : ''; ?>">Configurações</a>
</div>

<main>
    <div class="container my-5 py-5">
        <h2 class="text-center mb-5">RELATÓRIOS</h2>

        <h4 class="mb-4">Histórico de Agendamentos</h4>
        <div class="formulario-container mb-5">
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


        <h4 class="mb-4">Clientes Cadastrados</h4>
        <div class="table-responsive formulario-container">
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
</main>

<?php
include '../includes/footer.php';
?>