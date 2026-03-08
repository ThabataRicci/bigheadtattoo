<?php
session_start();
require_once '../includes/conexao.php'; // conexao com o Clever Cloud

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$titulo_pagina = "Meus Agendamentos";
include '../includes/header.php';

$projetos_para_agendar = [];
$proximas_sessoes = [];
$orcamentos_pendentes = [];
$historico = [];

try {
    // 1. buscar orçamentos pendentes (em análise)
    $sql_pendentes = "SELECT * FROM orcamento WHERE id_usuario = ? AND (status = 'Pendente' OR status IS NULL)";
    $stmt = $pdo->prepare($sql_pendentes);
    $stmt->execute([$id_usuario]);
    $resultados_pendentes = $stmt->fetchAll();

    foreach ($resultados_pendentes as $row) {
        $ideia_completa = htmlspecialchars($row['descricao_ideia']);
        $titulo_dinamico = mb_strimwidth($ideia_completa, 0, 30, "...");

        $orcamentos_pendentes[] = [
            'id' => $row['id_orcamento'],
            'titulo' => $titulo_dinamico,
            'status' => 'Aguardando Análise',
            'status_class' => 'status-analise',
            'local' => htmlspecialchars($row['local_corpo']),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
            'ideia' => '"' . $ideia_completa . '"',
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'detalhe_status' => 'Sua ideia foi enviada e está com o artista para análise.'
        ];
    }

    // 2. buscar próximas sessões agendadas
    $sql_sessoes = "SELECT s.id_sessao, s.data_hora, p.titulo, p.id_projeto 
                    FROM sessao s 
                    JOIN projeto p ON s.id_projeto = p.id_projeto 
                    WHERE p.id_usuario = ? AND s.status = 'Aguardada' AND s.data_hora >= NOW() 
                    ORDER BY s.data_hora ASC";
    $stmt = $pdo->prepare($sql_sessoes);
    $stmt->execute([$id_usuario]);
    $resultados_sessoes = $stmt->fetchAll();

    foreach ($resultados_sessoes as $row) {
        $data_obj = new DateTime($row['data_hora']);
        $proximas_sessoes[] = [
            'id' => $row['id_sessao'],
            'tamanho_cod' => 'NA',
            'titulo' => htmlspecialchars($row['titulo']),
            'data' => $data_obj->format('d/m/Y \à\s H:i'),
            'local' => 'Definido no projeto',
            'tamanho_desc' => 'Definido no projeto',
            'ideia' => 'Sessão confirmada.',
            'ref' => 'Sem referência',
            'duracao' => 'A definir',
            'historico_sessoes' => [
                ['desc' => 'Sessão agendada para ' . $data_obj->format('d/m/Y H:i'), 'pode_cancelar' => true]
            ]
        ];
    }

    // 3. buscar orçamentos aprovados (ação requerida)
    $sql_aprovados = "SELECT * FROM orcamento WHERE id_usuario = ? AND status = 'Aprovado'";
    $stmt = $pdo->prepare($sql_aprovados);
    $stmt->execute([$id_usuario]);
    $resultados_aprovados = $stmt->fetchAll();

    foreach ($resultados_aprovados as $row) {
        $ideia_completa = htmlspecialchars($row['descricao_ideia']);
        $titulo_dinamico = mb_strimwidth($ideia_completa, 0, 30, "...");

        $projetos_para_agendar[] = [
            'id' => $row['id_orcamento'],
            'titulo' => $titulo_dinamico,
            'status' => 'Agende sua sessão',
            'status_class' => 'status-acao',
            'local' => htmlspecialchars($row['local_corpo']),
            'tamanho_cod' => 'NA',
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
            'ideia' => '"' . $ideia_completa . '"',
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'duracao' => 'A ser definida pelo artista na sessão'
        ];
    }

    // 4. buscar histórico (orçamentos recusados)
    $sql_recusados = "SELECT * FROM orcamento WHERE id_usuario = ? AND status = 'Recusado'";
    $stmt = $pdo->prepare($sql_recusados);
    $stmt->execute([$id_usuario]);
    $resultados_recusados = $stmt->fetchAll();

    foreach ($resultados_recusados as $row) {
        $ideia_completa = htmlspecialchars($row['descricao_ideia']);
        $titulo_dinamico = mb_strimwidth($ideia_completa, 0, 30, "...");

        $historico[] = [
            'tipo' => 'recusado',
            'titulo' => $titulo_dinamico,
            'status' => 'Recusado',
            'status_class' => 'status-cancelado',
            'local' => htmlspecialchars($row['local_corpo']),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
            'ideia' => '"' . $ideia_completa . '"',
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'detalhe_status' => 'O artista avaliou sua ideia, mas infelizmente não poderá realizá-la no momento. Tente enviar uma nova proposta para outro projeto!'
        ];
    }

    // 5. buscar histórico (sessões concluídas ou canceladas)
    $sql_hist_sessoes = "SELECT s.id_sessao, s.data_hora, s.status, p.titulo 
                         FROM sessao s 
                         JOIN projeto p ON s.id_projeto = p.id_projeto 
                         WHERE p.id_usuario = ? AND s.status IN ('Concluída', 'Cancelada') 
                         ORDER BY s.data_hora DESC";
    $stmt = $pdo->prepare($sql_hist_sessoes);
    $stmt->execute([$id_usuario]);
    $resultados_hist_sessoes = $stmt->fetchAll();

    foreach ($resultados_hist_sessoes as $row) {
        $data_obj = new DateTime($row['data_hora']);
        $status_amigavel = $row['status'];
        $status_class = ($status_amigavel == 'Concluída') ? 'status-concluido' : 'status-cancelado';

        $historico[] = [
            'tipo' => 'concluido',
            'titulo' => htmlspecialchars($row['titulo']),
            'status' => $status_amigavel,
            'status_class' => $status_class,
            'local' => 'Definido no projeto',
            'tamanho_desc' => 'Definido no projeto',
            'ideia' => 'Sessão ' . strtolower($status_amigavel) . '.',
            'ref' => 'Sem referência',
            'detalhe_sessao' => '<p class="text-white-50 mb-2 mt-4"><strong>Detalhes da Sessão:</strong></p>
                                 <div class="small mb-3">
                                     <p class="mb-1"><strong>Data da Sessão:</strong> ' . $data_obj->format('d/m/Y') . '</p>
                                     <p class="mb-0"><strong>Status Final:</strong> ' . $status_amigavel . '</p>
                                 </div>'
        ];
    }
} catch (PDOException $e) {
}
?>

<?php
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    $link_prefix = '';

    echo '<div class="submenu-painel">';
    if ($_SESSION['usuario_perfil'] == 'artista') {
        echo '<a href="' . $link_prefix . 'dashboard-artista.php" class="' . ($pagina_ativa == 'dashboard-artista.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="' . $link_prefix . 'agenda.php" class="' . ($pagina_ativa == 'agenda.php' ? 'active' : '') . '">Agenda</a>';
        echo '<a href="' . $link_prefix . 'portfolio-artista.php" class="' . ($pagina_ativa == 'portfolio-artista.php' ? 'active' : '') . '">Portfólio</a>';
        echo '<a href="' . $link_prefix . 'relatorios-artista.php" class="' . ($pagina_ativa == 'relatorios-artista.php' ? 'active' : '') . '">Relatórios</a>';
        echo '<a href="' . $link_prefix . 'configuracoes-artista.php" class="' . ($pagina_ativa == 'configuracoes-artista.php' ? 'active' : '') . '">Configurações</a>';
    } else {
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

        <h4 class="mb-4">Ação Requerida</h4>

        <?php if (empty($projetos_para_agendar)): ?>
            <div class="card-resumo text-center text-white-50 mb-5">
                Você não tem nenhuma ação pendente no momento.
            </div>
        <?php else: ?>
            <div class="accordion mb-5" id="acordeaoAcaoRequerida">
                <?php foreach ($projetos_para_agendar as $i => $proj): ?>
                    <div class="accordion-item card-acao mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-acao-<?php echo $i; ?>">
                                <div class="w-100 d-flex justify-content-between align-items-center">
                                    <span><strong>Projeto:</strong> <?php echo $proj['titulo']; ?></span>
                                    <span class="badge <?php echo $proj['status_class']; ?> me-3"><?php echo $proj['status']; ?></span>
                                </div>
                            </button>
                        </h2>
                        <div id="item-acao-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoAcaoRequerida">
                            <div class="accordion-body">
                                <p class="text-white-50 mb-2"><strong>Detalhes do Orçamento Aprovado:</strong></p>
                                <div class="small mb-3">
                                    <p class="mb-1"><strong>Local do Corpo:</strong> <?php echo $proj['local']; ?></p>
                                    <p class="mb-1"><strong>Tamanho Aproximado:</strong> <?php echo $proj['tamanho_desc']; ?></p>
                                    <p class="mb-1"><strong>Sua Ideia:</strong> <?php echo $proj['ideia']; ?></p>
                                    <p class="mb-1"><strong>Referência Enviada:</strong>
                                        <?php if ($proj['ref'] !== 'Sem referência' && $proj['ref'] !== ''): ?>
                                            <a href="../imagens/orcamentos/<?php echo $proj['ref']; ?>" target="_blank" class="text-info text-decoration-none">
                                                <i class="bi bi-image me-1"></i> Ver Anexo
                                            </a>
                                        <?php else: ?>
                                            <span class="text-white-50">Vazio</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mb-0"><strong>Duração da Sessão:</strong> <?php echo $proj['duracao']; ?></p>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="agenda.php?projeto_id=<?php echo $proj['id']; ?>&tamanho=<?php echo $proj['tamanho_cod']; ?>" class="btn btn-secondary ">AGENDAR SESSÃO</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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

        <div class="tab-content tab-content-boxed" id="abasProjetosConteudo">

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
                                        <p class="text-white-50 mb-2"><strong>Detalhes:</strong></p>
                                        <div class="small mb-3">
                                            <p class="mb-1"><strong>Local do Corpo:</strong> <?php echo $sessao['local']; ?></p>
                                            <p class="mb-1"><strong>Tamanho Aproximado:</strong> <?php echo $sessao['tamanho_desc']; ?></p>
                                            <p class="mb-1"><strong>Sua Ideia:</strong> <?php echo $sessao['ideia']; ?></p>
                                            <p class="mb-0"><strong>Duração da Sessão:</strong> <?php echo $sessao['duracao']; ?></p>
                                        </div>
                                        <p class="text-white-50 mb-2 mt-4"><strong>Histórico de Sessões:</strong></p>
                                        <div class="p-3" style="background-color: #2c2c2c; border-radius: 8px;">
                                            <?php foreach ($sessao['historico_sessoes'] as $hist): ?>
                                                <div class="d-flex justify-content-between align-items-center small p-2">
                                                    <span><?php echo $hist['desc']; ?></span>
                                                    <?php if ($hist['pode_cancelar']): ?>
                                                        <div class="d-flex gap-2">
                                                            <a href="agenda.php?projeto_id=<?php echo $sessao['id']; ?>" class="btn btn-sm btn-outline-light">Reagendar</a>
                                                            <button class="btn btn-sm btn-outline-danger btn-cancelar-sessao" data-id="<?php echo $sessao['id']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelarCliente">Cancelar</button>
                                                        </div>
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
                                        <p class="text-white-50 mb-2"><strong>Detalhes da Solicitação:</strong></p>
                                        <div class="small mb-3">
                                            <p class="mb-1"><strong>Local do Corpo:</strong> <?php echo $proj['local']; ?></p>
                                            <p class="mb-1"><strong>Tamanho Aproximado:</strong> <?php echo $proj['tamanho_desc']; ?></p>
                                            <p class="mb-1"><strong>Sua Ideia:</strong> <?php echo $proj['ideia']; ?></p>
                                            <p class="mb-0"><strong>Referência Enviada:</strong>
                                                <?php if ($proj['ref'] !== 'Sem referência' && $proj['ref'] !== ''): ?>
                                                    <a href="../imagens/orcamentos/<?php echo $proj['ref']; ?>" target="_blank" class="text-info text-decoration-none">
                                                        <i class="bi bi-image me-1"></i> Ver Anexo
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-white-50">Vazio</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <p class="mt-4"><?php echo $proj['detalhe_status']; ?></p>
                                        <div class="text-end mt-3">
                                            <button class="btn btn-sm btn-outline-danger btn-cancelar-orc" data-id="<?php echo $proj['id']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelarOrcamento">Cancelar Solicitação</button>
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
                                        <p class="text-white-50 mb-2"><strong>Detalhes do Projeto:</strong></p>
                                        <div class="small mb-3">
                                            <p class="mb-1"><strong>Local do Corpo:</strong> <?php echo $item['local']; ?></p>
                                            <p class="mb-1"><strong>Tamanho Aproximado:</strong> <?php echo $item['tamanho_desc']; ?></p>
                                            <p class="mb-1"><strong>Sua Ideia:</strong> <?php echo $item['ideia']; ?></p>
                                            <p class="mb-0"><strong>Referência Enviada:</strong>
                                                <?php if ($item['ref'] !== 'Sem referência' && $item['ref'] !== ''): ?>
                                                    <a href="../imagens/orcamentos/<?php echo $item['ref']; ?>" target="_blank" class="text-info text-decoration-none">
                                                        <i class="bi bi-image me-1"></i> Ver Anexo
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-white-50">Vazio</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <?php if ($item['tipo'] == 'recusado'): ?>
                                            <p class="text-white-50 mb-2 mt-4"><strong>Motivo:</strong></p>
                                            <div class="bg-dark p-3 rounded fst-italic">
                                                <small class="mb-0"><?php echo $item['detalhe_status']; ?></small>
                                            </div>
                                        <?php else: ?>
                                            <?php echo $item['detalhe_sessao']; ?>
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
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Cancelar Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Você tem certeza que deseja cancelar esta sessão?</p>
                <form action="../actions/a.cancelar-sessao.php" method="POST">
                    <input type="hidden" name="sessao_id" id="inputSessaoId" value="">
                    <div class="modal-footer border-top border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCancelarOrcamento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Cancelar Solicitação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Você tem certeza que deseja cancelar esta solicitação de orçamento?</p>
                <p class="small text-muted">O artista ainda não analisou este pedido. Ao cancelar, ele será removido da fila.</p>
                <form action="../actions/a.cancelar-orcamento.php" method="POST">
                    <input type="hidden" name="orcamento_id" id="inputOrcamentoId" value="">
                    <div class="modal-footer border-top border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica das abas
        const tabButtons = document.querySelectorAll('#abasProjetos .nav-link');
        const accordionCollapses = document.querySelectorAll('.accordion-collapse');
        const collapseInstances = Array.from(accordionCollapses).map(collapseEl => {
            return new bootstrap.Collapse(collapseEl, {
                toggle: false
            });
        });

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const collapsesInsideTabs = document.querySelectorAll('#abasProjetosConteudo .accordion-collapse');
                collapsesInsideTabs.forEach(collapseEl => {
                    const instance = bootstrap.Collapse.getInstance(collapseEl);
                    if (instance) instance.hide();
                });
            });
        });

        const acoesCollapse = document.querySelectorAll('#acordeaoAcaoRequerida .accordion-collapse');
        acoesCollapse.forEach(collapseEl => {
            collapseEl.addEventListener('show.bs.collapse', () => {
                acoesCollapse.forEach(otherCollapseEl => {
                    if (otherCollapseEl !== collapseEl) {
                        const instance = bootstrap.Collapse.getInstance(otherCollapseEl);
                        if (instance) instance.hide();
                    }
                });
            });
        });

        const btnsCancelarSessao = document.querySelectorAll('.btn-cancelar-sessao');
        const inputSessaoId = document.getElementById('inputSessaoId');
        btnsCancelarSessao.forEach(btn => {
            btn.addEventListener('click', function() {
                inputSessaoId.value = this.getAttribute('data-id');
            });
        });

        const btnsCancelarOrc = document.querySelectorAll('.btn-cancelar-orc');
        const inputOrcamentoId = document.getElementById('inputOrcamentoId');
        btnsCancelarOrc.forEach(btn => {
            btn.addEventListener('click', function() {
                inputOrcamentoId.value = this.getAttribute('data-id');
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>