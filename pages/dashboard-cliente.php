<?php
session_start();

$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "cliente";

$titulo_pagina = "Meu Painel";
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

        <h2 class="text-center mb-5">PAINEL INICIAL</h2>

        <div class="row justify-content-center g-5">

            <div class="col-lg-7">
                <h3 class="mb-4">Próximos Agendamentos</h3>

                <?php
                // quando tiver o banco, substituir esta parte pela consulta SQL
                // $proximos_agendamentos = $resultado_do_banco; 

                // teste/simulação:
                $proximos_agendamentos = ["dado1"]; // [] para ver o "estado vazio"
                // $proximos_agendamentos = ["dado1", "dado2"]; // ver o conteúdo
                ?>

                <?php if (empty($proximos_agendamentos)): ?>

                    <div class="card-resumo mb-3 text-center">
                        <p class="text-white-50 mb-0 py-3">Você ainda não possui sessões agendadas.</p>
                    </div>

                <?php else: ?>

                    <div class="card-resumo mb-3">
                        <p class="mb-1"><strong>Fechamento de Braço</strong></p>
                        <p class="text-white-50 small mb-0">Data: 08/11/2025 - Horário: 10:00</p>
                    </div>

                <?php endif; ?>
                <?php
                ?>

            </div>

            <div class="col-lg-4">
                <h3 class="mb-4 invisible">Ações</h3>

                <div class="card-resumo p-4">
                    <p class="text-white-50 mb-4">Acompanhe seus agendamentos ou solicite um novo orçamento.</p>
                    <div class="d-grid gap-2">
                        <a href="solicitar-orcamento.php" class="btn btn-primary">SOLICITAR ORÇAMENTO</a>
                        <a href="agendamentos-cliente.php" class="btn btn-outline-light">VER TODOS OS AGENDAMENTOS</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>