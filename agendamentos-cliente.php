<?php
session_start();

// Simula o login do cliente para teste
$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "cliente";

$titulo_pagina = "Meus Agendamentos";
include 'includes/header.php';
?>
<main>
    <div class="container my-5 py-5">
        <h2 class="text-center mb-5">MEUS AGENDAMENTOS</h2>

        <div class="text-center mb-4">
            <a href="#" class="btn btn-sm btn-light">Todos</a>
            <a href="#" class="btn btn-sm btn-outline-light">Em Andamento</a>
            <a href="#" class="btn btn-sm btn-outline-light">Ação Necessária</a>
            <a href="#" class="btn btn-sm btn-outline-light">Concluídos</a>
            <a href="#" class="btn btn-sm btn-outline-light">Cancelados</a>
        </div>

        <div class="accordion" id="acordeaoAgendamentos">

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#item-acao">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Fechamento de Costas</span>
                            <span class="badge status-acao me-3">Ação Necessária</span>
                        </div>
                    </button>
                </h2>
                <div id="item-acao" class="accordion-collapse collapse show" data-bs-parent="#acordeaoAgendamentos">
                    <div class="accordion-body">
                        <p>Aguardando agendamento de sessão. Escolha seu horário no botão disponível abaixo.</p>
                        <p><strong>Detalhes do Projeto:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local:</strong> Costas (Fechamento)</li>
                            <li><strong>Tamanho:</strong> Fechamento</li>
                            <li><strong>Sua Ideia:</strong> "Gostaria de fechar as costas com um dragão oriental..."</li>
                            <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_imagem_costas.jpg</a></li>
                            <li><strong>Estrutura (Artista):</strong> 3 sessões de 4 horas cada</li>
                        </ul>
                        <p><strong>Histórico de Sessões:</strong></p>
                        <div class="p-2 mb-2 d-flex justify-content-between align-items-center">
                            <p class="mb-0 small"><strong>Sessão 1:</strong> Pendente</p>
                            <button onclick="window.location.href='agendar-horario.php?projeto_id=456&sessao=1'" class="btn btn-sm btn-primary">Agendar</button>
                        </div>
                        <div class="p-2 mb-2 d-flex justify-content-between align-items-center opacity-50">
                            <p class="mb-0 small"><strong>Sessão 2:</strong> Pendente</p>
                            <button class="btn btn-sm btn-secondary" disabled>Agendar</button>
                        </div>
                        <div class="p-2 d-flex justify-content-between align-items-center opacity-50">
                            <p class="mb-0 small"><strong>Sessão 3:</strong> Pendente</p>
                            <button class="btn btn-sm btn-secondary" disabled>Agendar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-andamento">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Fechamento de Braço</span>
                            <span class="badge status-andamento me-3">Em Andamento (1/3)</span>
                        </div>
                    </button>
                </h2>
                <div id="item-andamento" class="accordion-collapse collapse" data-bs-parent="#acordeaoAgendamentos">
                    <div class="accordion-body">
                        <p>Projeto em andamento.</p>
                        <p><strong>Detalhes do Projeto:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local:</strong> Braço</li>
                            <li><strong>Tamanho:</strong> Fechamento</li>
                            <li><strong>Sua Ideia:</strong> "Um projeto de fechamento de braço com tema maori..."</li>
                            <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_imagem_braco.jpg</a></li>
                            <li><strong>Estrutura (Artista):</strong> 3 sessões de 4 horas cada</li>
                        </ul>
                        <p><strong>Histórico de Sessões:</strong></p>
                        <div class="p-2 mb-2">
                            <p class="mb-0 small"><strong>Sessão 1:</strong> Concluída em 20/09/2025</p>
                        </div>
                        <div class="p-2 mb-2 d-flex justify-content-between align-items-center">
                            <p class="mb-0 small"><strong>Sessão 2:</strong> Agendada para 25/10/2025 às 14:00</p><button class="btn btn-sm btn-outline-danger">Cancelar</button>
                        </div>
                        <div class="p-2 d-flex justify-content-between align-items-center opacity-50">
                            <p class="mb-0 small"><strong>Sessão 3:</strong> Pendente</p><button class="btn btn-sm btn-secondary" disabled>Agendar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-analise">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Tatuagem Fineline</span>
                            <span class="badge status-analise me-3">Aguardando Análise</span>
                        </div>
                    </button>
                </h2>
                <div id="item-analise" class="accordion-collapse collapse" data-bs-parent="#acordeaoAgendamentos">
                    <div class="accordion-body">
                        <p>Aguardando análise do artista.</p>
                        <p><strong>Detalhes do Projeto:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local:</strong> Costas</li>
                            <li><strong>Tamanho:</strong> Pequeno (até 10cm)</li>
                            <li><strong>Sua Ideia:</strong> "Uma frase em fineline na vertical, perto da coluna..."</li>
                            <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_referencia.jpg</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-concluido">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Rosa no Antebraço</span>
                            <span class="badge status-concluido me-3">Concluído</span>
                        </div>
                    </button>
                </h2>
                <div id="item-concluido" class="accordion-collapse collapse" data-bs-parent="#acordeaoAgendamentos">
                    <div class="accordion-body">
                        <p>Projeto concluído.</p>
                        <p><strong>Detalhes do Projeto:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local:</strong> Antebraço</li>
                            <li><strong>Tamanho:</strong> Médio (aprox. 15cm)</li>
                            <li><strong>Sua Ideia:</strong> "Uma rosa com traços finos e um pouco de sombra..."</li>
                            <li><strong>Referência Enviada:</strong> Nenhuma</li>
                            <li><strong>Estrutura (Artista):</strong> 1 sessão de 3 horas</li>
                        </ul>
                        <p><strong>Histórico de Sessões:</strong></p>
                        <div class="p-2">
                            <p class="mb-0 small"><strong>Sessão 1:</strong> Concluída em 15/08/2025</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-cancelado">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <span><strong>Projeto:</strong> Tatuagem Geométrica</span>
                            <span class="badge status-cancelado me-3">Cancelado</span>
                        </div>
                    </button>
                </h2>
                <div id="item-cancelado" class="accordion-collapse collapse" data-bs-parent="#acordeaoAgendamentos">
                    <div class="accordion-body">
                        <p>Projeto cancelado conforme motivo informado abaixo.</p>
                        <p><strong>Detalhes do Projeto:</strong></p>
                        <ul class="list-unstyled card-resumo p-3 small">
                            <li><strong>Local:</strong> Perna</li>
                            <li><strong>Tamanho:</strong> Grande</li>
                            <li><strong>Sua Ideia:</strong> "Um lobo com formas geométricas..."</li>
                            <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_imagem_geometrica.jpg</a></li>
                            <li><strong>Estrutura (Artista):</strong> 2 sessões de 5 horas cada</li>
                        </ul>
                        <p class="text-white-50 mb-2"><strong>Motivo do cancelamento:</strong></p>
                        <div class="bg-dark p-3 rounded fst-italic">
                            <small class="mb-0">"Olá! Agradeço o interesse, mas no momento não estou realizando trabalhos nesta parte do corpo. Recomendo procurar outro profissional especializado."</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>