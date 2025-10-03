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
        <h2 class="text-center mb-5">GERENCIAR AGENDA</h2>

        <h4 class="mb-4">Solicitações Pendentes</h4>
        <div class="accordion" id="acordeaoSolicitacoes">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#item1">
                        <div class="w-100 d-flex justify-content-between">
                            <span><strong>Cliente:</strong> Izabella Bianca | <strong>Ideia:</strong> Fechamento de costas</span>
                        </div>
                    </button>
                </h2>
                <div id="item1" class="accordion-collapse collapse show" data-bs-parent="#acordeaoSolicitacoes">
                    <div class="accordion-body">
                        <p><strong>Ideia do Cliente:</strong> "Gostaria de iniciar um projeto de fechamento de costas com um dragão oriental..."</p>
                        <hr>
                        <form action="processa_aprovacao.php" method="POST">
                            <div class="row align-items-end">
                                <div class="col-md-5 mb-3 mb-md-0">
                                    <label for="duracao_sessao" class="form-label">Duração por Sessão (horas):</label>
                                    <input type="number" class="form-control" id="duracao_sessao" name="duracao_sessao" min="1">
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <label for="numero_sessoes" class="form-label">Nº de Sessões Estimadas:</label>
                                    <input type="number" class="form-control" id="numero_sessoes" name="numero_sessoes" min="1">
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalRecusar">Recusar</button>
                                    <button type="submit" class="btn btn-sm btn-success ms-2">Aprovar Projeto</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Próximas Sessões Agendadas</h4>
            <div>
                <a href="#" class="btn btn-sm btn-outline-light">Hoje</a>
                <a href="#" class="btn btn-sm btn-outline-light">Amanhã</a>
                <a href="#" class="btn btn-sm btn-outline-light">Esta Semana</a>
                <a href="#" class="btn btn-sm btn-outline-light">Este Mês</a>
            </div>
        </div>

        <div class="accordion" id="acordeaoSessoesAgendadas">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessaoAgendada1">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Cliente:</strong> Thábata Ricci | <strong>Sessão 2/3</strong></span>
                            <span class="me-3"><strong>Data:</strong> 25/10/2025 às 14:00</span>
                        </div>
                    </button>
                </h2>
                <div id="sessaoAgendada1" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                    <div class="accordion-body">
                        <p class="text-white-50 mb-2"><strong>Detalhes da Solicitação do Cliente:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local:</strong> Braço (Fechamento)</li>
                            <li><strong>Sua Ideia:</strong> "Um projeto de fechamento de braço com tema maori..."</li>
                            <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_imagem_braco.jpg</a></li>
                            <li><strong>Duração da Sessão:</strong> 4 horas</li>
                        </ul>
                        <div class="text-end mt-3">
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                        </div>
                    </div>
                </div>
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
                <form action="processa_recusa.php" method="POST">
                    <div class="mb-3">
                        <label for="motivo_recusa" class="form-label">Motivo da recusa:</label>
                        <textarea class="form-control" id="motivo_recusa" name="motivo_recusa" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="solicitacao_id" value="101">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Recusa</button>
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
                        <label for="motivo_cancelamento" class="form-label">Motivo do cancelamento:</label>
                        <textarea class="form-control" id="motivo_cancelamento" name="motivo_cancelamento" rows="3" required></textarea>
                        <div class="form-text">O cliente será notificado com esta mensagem para que possa reagendar.</div>
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
include '../footer.php';
?>