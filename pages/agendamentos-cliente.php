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
        <h2 class="text-center mb-5">MEUS AGENDAMENTOS</h2>

        <?php

        $projetos_para_agendar = [
            [
                'id' => 101,
                'titulo' => 'Fechamento de Costas',
                'status' => 'Agende sua sessão',
                'status_class' => 'status-acao',
                'local' => 'Costas',
                'tamanho_cod' => 'PG',
                'tamanho_desc' => 'Fechamento',
                'ideia' => '"Gostaria de fechar as costas com um dragão oriental..."',
                'ref' => 'ver_imagem_dragao.jpg',
                'duracao' => 'Dia Todo'
            ]
        ];

        $proximas_sessoes = [
            [
                'titulo' => 'Fechamento de Perna',
                'data' => '28/10/2025 às 10:00',
                'local' => 'Perna',
                'tamanho_desc' => 'Fechamento',
                'ideia' => '"Projeto para fechar a perna."',
                'ref' => 'ver_referencia.jpg',
                'duracao' => 'Dia Todo',
                'historico_sessoes' => [
                    ['desc' => 'Sessão 1: Concluída em 01/10/2025', 'pode_cancelar' => false],
                    ['desc' => 'Sessão 2: Agendada para 28/10/2025 às 10:00', 'pode_cancelar' => true]
                ]
            ]
        ];

        $orcamentos_pendentes = [
            [
                'titulo' => 'Tatuagem Fineline',
                'status' => 'Aguardando Análise',
                'status_class' => 'status-analise',
                'detalhe' => 'Sua ideia foi enviada e está com o artista para análise.'
            ]
        ];

        $historico = [
            [
                'tipo' => 'concluido',
                'titulo' => 'Rosa no Antebraço',
                'status' => 'Concluído',
                'status_class' => 'status-concluido',
                'detalhe' => '<p class="text-white-50 mb-2"><strong>Detalhes do Projeto:</strong></p>
                             <ul class="list-unstyled card-resumo p-3 small">
                                 <li><strong>Local do Corpo:</strong> Antebraço</li>
                                 <li><strong>Tamanho Aproximado:</strong> Médio (aprox. 15cm)</li>
                                 <li><strong>Sua Ideia:</strong> "Uma rosa com traços finos e um pouco de sombra..."</li>
                                 <li><strong>Duração da Sessão:</strong> 2 horas</li>
                                 <li><strong>Data da Sessão:</strong> 15/08/2025</li>
                             </ul>'
            ],
            [
                'tipo' => 'recusado',
                'titulo' => 'Tatuagem Geométrica',
                'status' => 'Recusado',
                'status_class' => 'status-cancelado',
                'detalhe' => '"Olá! Agradeço o interesse, mas no momento não estou trabalhando com este tipo de projeto."'
            ]
        ];
        ?>

        <h4 class="mb-4">Ação Requerida</h4>

        <?php if (empty($projetos_para_agendar)): ?>
            <div class="card-resumo text-center text-white-50 mb-5">
                Você não tem nenhuma ação pendente no momento.
            </div>
        <?php else: ?>
            <?php foreach ($projetos_para_agendar as $proj): ?>
                <div class="card-resumo card-acao mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><?php echo $proj['titulo']; ?></h5>
                        <span class="badge <?php echo $proj['status_class']; ?>"><?php echo $proj['status']; ?></span>
                    </div>
                    <p class="text-white-50 mb-2"><strong>Detalhes do Orçamento Aprovado:</strong></p>
                    <ul class="list-unstyled card-resumo p-3 small">
                        <li><strong>Local do Corpo:</strong> <?php echo $proj['local']; ?></li>
                        <li><strong>Tamanho Aproximado:</strong> <?php echo $proj['tamanho_desc']; ?></li>
                        <li><strong>Sua Ideia:</strong> <?php echo $proj['ideia']; ?></li>
                        <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50"><?php echo $proj['ref']; ?></a></li>
                        <li><strong>Duração da Sessão:</strong> <?php echo $proj['duracao']; ?></li>
                    </ul>
                    <div class="text-end mt-3">
                        <a href="agenda.php?projeto_id=<?php echo $proj['id']; ?>&tamanho=<?php echo $proj['tamanho_cod']; ?>" class="btn btn-secondary ">AGENDAR SESSÃO</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>


        <h4 class="mb-4">Meus Projetos</h4>

        <ul class="nav nav-tabs nav-tabs-dark mb-4" id="abasProjetos" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="proximas-tab" data-bs-toggle="tab" data-bs-target="#tab-proximas" type="button" role="tab" aria-controls="tab-proximas" aria-selected="true">Próximas Sessões</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analise-tab" data-bs-toggle="tab" data-bs-target="#tab-analise" type="button" role="tab" aria-controls="tab-analise" aria-selected="false">Em Análise</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button" role="tab" aria-controls="tab-historico" aria-selected="false">Histórico</button>
            </li>
        </ul>

        <div class="tab-content" id="abasProjetosConteudo">

            <div class="tab-pane fade show active" id="tab-proximas" role="tabpanel" aria-labelledby="proximas-tab">
                <?php if (empty($proximas_sessoes)): ?>
                    <div class="card-resumo text-center text-white-50">
                        Você não possui nenhuma sessão agendada.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoProximasSessoes">
                        <?php foreach ($proximas_sessoes as $i => $sessao): ?>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessao-<?php echo $i; ?>">
                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                            <span><strong>Projeto:</strong> <?php echo $sessao['titulo']; ?></span>
                                            <span class="me-3"><strong>Próxima Sessão:</strong> <?php echo $sessao['data']; ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="sessao-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoProximasSessoes">
                                    <div class="accordion-body">
                                        <p class="text-white-50 mb-2"><strong>Detalhes do Orçamento Aprovado:</strong></p>
                                        <ul class="list-unstyled card-resumo p-3 small">
                                            <li><strong>Local do Corpo:</strong> <?php echo $sessao['local']; ?></li>
                                            <li><strong>Tamanho Aproximado:</strong> <?php echo $sessao['tamanho_desc']; ?></li>
                                            <li><strong>Sua Ideia:</strong> <?php echo $sessao['ideia']; ?></li>
                                            <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50"><?php echo $sessao['ref']; ?></a></li>
                                            <li><strong>Duração da Sessão:</strong> <?php echo $sessao['duracao']; ?></li>
                                        </ul>
                                        <p class="text-white-50 mb-2 mt-4"><strong>Histórico de Sessões:</strong></p>
                                        <div class="card-resumo p-3">
                                            <?php foreach ($sessao['historico_sessoes'] as $hist): ?>
                                                <div class="d-flex justify-content-between align-items-center small p-2">
                                                    <span><?php echo $hist['desc']; ?></span>
                                                    <?php if ($hist['pode_cancelar']): ?>
                                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelarCliente">Cancelar</button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tab-analise" role="tabpanel" aria-labelledby="analise-tab">
                <?php if (empty($orcamentos_pendentes)): ?>
                    <div class="card-resumo text-center text-white-50">
                        Você não possui nenhum orçamento em análise.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoPendentes">
                        <?php foreach ($orcamentos_pendentes as $i => $proj): ?>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-analise-<?php echo $i; ?>">
                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                            <span><strong>Projeto:</strong> <?php echo $proj['titulo']; ?></span>
                                            <span class="badge <?php echo $proj['status_class']; ?> me-3"><?php echo $proj['status']; ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="item-analise-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoPendentes">
                                    <div class="accordion-body">
                                        <p><?php echo $proj['detalhe']; ?></p>

                                        <div class="text-end mt-3">
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelarOrcamento">Cancelar Solicitação</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tab-historico" role="tabpanel" aria-labelledby="historico-tab">
                <?php if (empty($historico)): ?>
                    <div class="card-resumo text-center text-white-50">
                        Seu histórico está vazio.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoHistorico">
                        <?php foreach ($historico as $i => $item): ?>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-hist-<?php echo $i; ?>">
                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                            <span><strong>Projeto:</strong> <?php echo $item['titulo']; ?></span>
                                            <span class="badge <?php echo $item['status_class']; ?> me-3"><?php echo $item['status']; ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="item-hist-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoHistorico">
                                    <div class="accordion-body">
                                        <?php if ($item['tipo'] == 'recusado'): ?>
                                            <p class="text-white-50 mb-2"><strong>Motivo:</strong></p>
                                            <div class="bg-dark p-3 rounded fst-italic">
                                                <small class="mb-0"><?php echo $item['detalhe']; ?></small>
                                            </div>
                                        <?php else: ?>

                                            <?php echo $item['detalhe']; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>


<div class="modal fade" id="modalCancelarCliente" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja cancelar esta sessão?</p>
                <form onsubmit="alert('Sessão cancelada (simulação).'); return false;">
                    <input type="hidden" name="sessao_id" value="ID_DA_SESSAO_A_CANCELAR">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger" data-bs-dismiss="modal">Confirmar Cancelamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCancelarOrcamento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelar Solicitação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja cancelar esta solicitação de orçamento?</p>
                <p class="small text-white-50">O artista ainda não analisou este pedido. Ao cancelar, ele será removido da fila.</p>
                <form onsubmit="alert('Solicitação cancelada (simulação).'); return false;">
                    <input type="hidden" name="orcamento_id" value="ID_DO_ORCAMENTO_A_CANCELAR">
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