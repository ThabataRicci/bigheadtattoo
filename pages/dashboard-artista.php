<?php
session_start();

$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "artista";

$titulo_pagina = "Painel de Controle";
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

        <h2 class="text-center mb-5">PAINEL DE CONTROLE</h2>

        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="card-resumo">
                    <h3>1</h3>
                    <p class="text-white-50 mb-0">Solicitação para Aprovar</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-resumo">
                    <h3>2</h3>
                    <p class="text-white-50 mb-0">Sessões na Semana</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-resumo">
                    <h3>X</h3>
                    <p class="text-white-50 mb-0">Novos Clientes no Mês</p>
                </div>
            </div>
        </div>

        <hr class="my-5">

        <div class="row">
            <div class="col-lg-6 mb-4">
                <h4 class="mb-4">Solicitações Pendentes</h4>

                <?php
                ?>
                <?php $solicitacoes_pendentes = true; ?>
                <?php if (!$solicitacoes_pendentes): ?>
                    <div class="card-resumo text-center text-white-50 mb-0">
                        Nenhuma solicitação pendente.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoSolicitacoes">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item1">
                                    <div class="w-100 d-flex justify-content-between">
                                        <span><strong>Cliente:</strong> Izabella Bianca | <strong>Ideia:</strong> Fechamento de costas</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="item1" class="accordion-collapse collapse" data-bs-parent="#acordeaoSolicitacoes">
                                <div class="accordion-body">
                                    <p><strong>Local do Corpo:</strong> Costas</p>
                                    <p><strong>Tamanho Aproximado:</strong> Fechamento</p>
                                    <p><strong>Ideia do Cliente:</strong> "Gostaria de iniciar um projeto de fechamento de costas com um dragão oriental..."</p>
                                    <p><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_imagem_dragao.jpg</a></p>
                                    <div class="d-flex justify-content-end align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRecusar">Recusar</button>
                                        <div class="dropdown ms-2">
                                            <button class="btn btn-sm btn-success dropdown-toggle" type="button" id="dropdownAprovar" data-bs-toggle="dropdown" aria-expanded="false">
                                                Aprovar
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownAprovar">
                                                <li><a class="dropdown-item" href="#">Projeto Pequeno (30 minutos)</a></li>
                                                <li><a class="dropdown-item" href="#">Projeto Médio (2 horas)</a></li>
                                                <li><a class="dropdown-item" href="#">Projeto Grande (dia todo)</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-6 mb-4">
                <h4 class="mb-4">Próximas Sessões</h4>

                <?php // SIMULAÇÃO DE DADOS 
                ?>
                <?php $proximas_sessoes = true; ?>
                <?php if (!$proximas_sessoes): ?>
                    <div class="card-resumo text-center text-white-50 mb-0">
                        Nenhuma sessão agendada para os próximos dias.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoSessoesAgendadas">

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessaoPP">

                                    <div class="w-100 d-flex flex-column">
                                        <div class="d-flex justify-content-between w-100">
                                            <span><strong>Projeto:</strong> Tatuagem Fineline</span>
                                            <span class="me-3"><strong>Data:</strong> 20/10/2025 às 11:00</span>
                                        </div>
                                        <span class="mt-1 small text-white-50"><strong>Cliente:</strong> João Silva</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="sessaoPP" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                                <div class="accordion-body">
                                    <ul class="list-unstyled card-resumo p-3 small mb-0">
                                        <li><strong>Local do Corpo:</strong> Pulso</li>
                                        <li><strong>Duração da Sessão:</strong> 30 minutos</li>
                                    </ul>
                                    <div class="text-end mt-3">
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessaoPG">

                                    <div class="w-100 d-flex flex-column">
                                        <div class="d-flex justify-content-between w-100">
                                            <span><strong>Projeto:</strong> Fechamento de Perna</span>
                                            <span class="me-3"><strong>Data:</strong> 28/10/2025 às 10:00</span>
                                        </div>
                                        <span class="mt-1 small text-white-50"><strong>Cliente:</strong> Maria Oliveira</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="sessaoPG" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                                <div class="accordion-body">
                                    <ul class="list-unstyled card-resumo p-3 small mb-0">
                                        <li><strong>Local do Corpo:</strong> Perna</li>
                                        <li><strong>Duração da Sessão:</strong> Dia Todo</li>
                                    </ul>
                                    <div class="text-end mt-3">
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>


<div class="modal fade" id="modalRecusar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recusar Projeto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form onsubmit="alert('Projeto recusado.'); return false;">
                    <div class="mb-3">
                        <label for="motivo_recusa" class="form-label">Motivo:</label>
                        <textarea class="form-control" id="motivo_recusa" name="motivo_recusa" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="solicitacao_id" value="101">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger" data-bs-dismiss="modal">Recusar Projeto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCancelar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Sessão Agendada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form onsubmit="alert('Sessão cancelada.'); return false;">
                    <div class="mb-3">
                        <label for="motivo_cancelamento" class="form-label">Motivo:</label>
                        <textarea class="form-control" id="motivo_cancelamento" name="motivo_cancelamento" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="sessao_id" value="201">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger" data-bs-dismiss="modal">Confirmar Cancelamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<?php
include '../includes/footer.php';
?>