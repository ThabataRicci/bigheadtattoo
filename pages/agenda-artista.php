<?php
session_start();

// Simula o login do artista para teste
$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "artista";

$titulo_pagina = "Gerenciar Agenda";
include '../includes/header.php';
?>
<main>
    <div class="container my-5 py-5">
        <div class="text-center mb-4">
            <h2>GERENCIAR AGENDA</h2>
        </div>

        <!-- BOTÃO ALTERADO: Agora leva para a página do calendário -->
        <div class="text-end mb-5">
            <a href="agenda.php" class="btn btn-outline-light">
                <i class="bi bi-calendar-week me-2"></i>Calendário Completo
            </a>
        </div>

        <h4 class="mb-4">Solicitações Pendentes de Orçamento</h4>
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

        <hr class="my-5">

        <h4 class="mb-4">Próximas Sessões Agendadas</h4>
        <div class="accordion" id="acordeaoSessoesAgendadas">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessaoPP">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Cliente:</strong> João Silva | <strong>Projeto:</strong> Tatuagem Fineline</span>
                            <span class="me-3"><strong>Data:</strong> 20/10/2025 às 11:00</span>
                        </div>
                    </button>
                </h2>
                <div id="sessaoPP" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                    <div class="accordion-body">
                        <p class="text-white-50 mb-2"><strong>Detalhes:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local do Corpo:</strong> Pulso</li>
                            <li><strong>Tamanho Aproximado:</strong> Pequeno (até 10cm)</li>
                            <li><strong>Ideia do Cliente:</strong> "Uma pequena âncora em fineline no pulso."</li>
                            <li><strong>Referência Enviada:</strong> Nenhuma</li>
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
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Cliente:</strong> Maria Oliveira | <strong>Projeto:</strong> Fechamento de Perna</span>
                            <span class="me-3"><strong>Data:</strong> 28/10/2025 às 10:00</span>
                        </div>
                    </button>
                </h2>
                <div id="sessaoPG" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                    <div class="accordion-body">
                        <p class="text-white-50 mb-2"><strong>Detalhes:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local do Corpo:</strong> Perna</li>
                            <li><strong>Tamanho Aproximado:</strong> Fechamento</li>
                            <li><strong>Ideia do Cliente:</strong> "Projeto para fechar a perna."</li>
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
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <button class="btn btn-sm btn-primary">Liberar Nova Sessão</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAIS DA PÁGINA -->
<div class="modal fade" id="modalRecusar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recusar Projeto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="processa_recusa.php" method="POST">
                    <div class="mb-3">
                        <label for="motivo_recusa" class="form-label">Motivo:</label>
                        <textarea class="form-control" id="motivo_recusa" name="motivo_recusa" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="solicitacao_id" value="101">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Recusar Projeto</button>
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
                <form action="processa_cancelamento_artista.php" method="POST">
                    <div class="mb-3">
                        <label for="motivo_cancelamento" class="form-label">Motivo:</label>
                        <textarea class="form-control" id="motivo_cancelamento" name="motivo_cancelamento" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="sessao_id" value="201">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>