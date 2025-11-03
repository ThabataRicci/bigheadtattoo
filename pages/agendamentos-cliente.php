<?php
session_start();

$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "cliente";

$titulo_pagina = "Meus Agendamentos";
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
        <h2 class="text-center mb-5">ORÇAMENTOS E AGENDAMENTOS</h2>

        <h4 class="mb-4">Pendentes</h4>
        <div class="accordion" id="acordeaoPendentes">

            <div class="accordion-item mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-aprovado">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Fechamento de Costas</span>
                            <span class="badge status-acao me-3">Agende sua sessão</span>
                        </div>
                    </button>
                </h2>
                <div id="item-aprovado" class="accordion-collapse collapse" data-bs-parent="#acordeaoPendentes">
                    <div class="accordion-body">
                        <p class="text-white-50 mb-2"><strong>Detalhes do Orçamento Aprovado:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local do Corpo:</strong> Costas</li>
                            <li><strong>Tamanho Aproximado:</strong> Fechamento</li>
                            <li><strong>Sua Ideia:</strong> "Gostaria de fechar as costas com um dragão oriental..."</li>
                            <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_imagem_dragao.jpg</a></li>
                            <li><strong>Duração da Sessão:</strong> Dia Todo</li>
                        </ul>
                        <div class="text-end mt-3">
                            <a href="agenda.php?projeto_id=101&tamanho=PG" class="btn btn-primary btn-sm">AGENDAR SESSÃO</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-analise">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Tatuagem Fineline</span>
                            <span class="badge status-analise me-3">Aguardando Análise</span>
                        </div>
                    </button>
                </h2>
                <div id="item-analise" class="accordion-collapse collapse" data-bs-parent="#acordeaoPendentes">
                    <div class="accordion-body">
                        <p>Sua ideia foi enviada e está com o artista para análise. O status será atualizado assim que ele avaliar.</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-5">

        <h4 class="mb-4">Próximas Sessões Agendadas</h4>
        <div class="accordion" id="acordeaoProximasSessoes">

            <div class="accordion-item mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessaoPG">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Fechamento de Perna</span>
                            <span class="me-3"><strong>Próxima Sessão:</strong> 28/10/2025 às 10:00</span>
                        </div>
                    </button>
                </h2>
                <div id="sessaoPG" class="accordion-collapse collapse" data-bs-parent="#acordeaoProximasSessoes">
                    <div class="accordion-body">
                        <p class="text-white-50 mb-2"><strong>Detalhes do Orçamento Aprovado:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local do Corpo:</strong> Perna</li>
                            <li><strong>Tamanho Aproximado:</strong> Fechamento</li>
                            <li><strong>Sua Ideia:</strong> "Projeto para fechar a perna."</li>
                            <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_referencia.jpg</a></li>
                            <li><strong>Duração da Sessão:</strong> Dia Todo</li>
                        </ul>
                        <p class="text-white-50 mb-2 mt-4"><strong>Histórico de Sessões:</strong></p>
                        <div class="card-resumo p-3">
                            <div class="d-flex justify-content-between align-items-center small p-2">
                                <span><strong>Sessão 1:</strong> Concluída em 01/10/2025</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small p-2">
                                <span><strong>Sessão 2:</strong> Agendada para 28/10/2025 às 10:00</span>
                                <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-5">

        <h4 class="mb-4">Histórico</h4>
        <div class="accordion" id="acordeaoHistorico">

            <div class="accordion-item mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-concluido">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Rosa no Antebraço</span>
                            <span class="badge status-concluido me-3">Concluído</span>
                        </div>
                    </button>
                </h2>
                <div id="item-concluido" class="accordion-collapse collapse" data-bs-parent="#acordeaoHistorico">
                    <div class="accordion-body">
                        <p class="text-white-50 mb-2"><strong>Detalhes do Projeto:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local do Corpo:</strong> Antebraço</li>
                            <li><strong>Tamanho Aproximado:</strong> Médio (aprox. 15cm)</li>
                            <li><strong>Sua Ideia:</strong> "Uma rosa com traços finos e um pouco de sombra..."</li>
                            <li><strong>Referência Enviada:</strong> Nenhuma</li>
                            <li><strong>Duração da Sessão:</strong> 2 horas</li>
                        </ul>
                        <p class="mt-3"><strong>Sessão 1:</strong> Concluída em 15/08/2025</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-recusado">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Tatuagem Geométrica</span>
                            <span class="badge status-cancelado me-3">Recusado</span>
                        </div>
                    </button>
                </h2>
                <div id="item-recusado" class="accordion-collapse collapse" data-bs-parent="#acordeaoHistorico">
                    <div class="accordion-body">
                        <p class="text-white-50 mb-2"><strong>Motivo:</strong></p>
                        <div class="bg-dark p-3 rounded fst-italic">
                            <small class="mb-0">"Olá! Agradeço o interesse, mas no momento não estou trabalhando com este tipo de projeto."</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
include '../includes/footer.php';
?>