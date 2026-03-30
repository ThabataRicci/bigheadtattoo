<?php
session_start();
require_once '../includes/conexao.php';

// acesso apenas pra artista
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: login.php");
    exit();
}

$titulo_pagina = "Painel de Controle";
$id_artista = $_SESSION['usuario_id'];

// lógica do banco de dados:
// 1. busca os orçamentos pendentes (incluindo os que estão em negociação)
try {
    $stmt_pendentes = $pdo->query("SELECT COUNT(*) FROM orcamento WHERE status = 'Pendente' OR status IS NULL OR status = 'Negociacao'");
    $qtd_pendentes = $stmt_pendentes->fetchColumn();

    $sql_pendentes_lista = "SELECT o.*, u.nome AS nome_cliente 
                            FROM orcamento o 
                            JOIN usuario u ON o.id_usuario = u.id_usuario 
                            WHERE o.status = 'Pendente' OR o.status IS NULL OR o.status = 'Negociacao'
                            ORDER BY o.data_envio ASC LIMIT 5";
    $solicitacoes_pendentes = $pdo->query($sql_pendentes_lista)->fetchAll();
} catch (PDOException $e) {
    $qtd_pendentes = 0;
    $solicitacoes_pendentes = [];
}
// 1.5 busca as propostas enviadas (aguardando cliente agendar)
try {
    $stmt_enviadas = $pdo->query("SELECT COUNT(*) FROM orcamento WHERE status = 'Aguardando Aceite'");
    $qtd_enviadas = $stmt_enviadas->fetchColumn();
} catch (PDOException $e) {
    $qtd_enviadas = 0;
}
// 2. busca sessoes e clientes
try {
    $stmt_sessoes = $pdo->query("SELECT COUNT(*) FROM sessao WHERE status = 'Agendado' AND data_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)");
    $qtd_sessoes_semana = $stmt_sessoes->fetchColumn();

    $stmt_clientes = $pdo->query("SELECT COUNT(*) FROM usuario WHERE perfil = 'cliente' AND MONTH(data_cadastro) = MONTH(NOW()) AND YEAR(data_cadastro) = YEAR(NOW())");
    $qtd_novos_clientes = $stmt_clientes->fetchColumn();

    $sql_sessoes_lista = "SELECT s.id_sessao, s.data_hora, p.titulo, p.id_projeto, u.nome AS nome_cliente, 
                                 o.descricao_ideia, o.local_corpo, o.referencia_ideia, o.qtd_sessoes,
                                 COALESCE(s.valor_sessao, o.valor_sessao) AS valor_sessao, 
                                 COALESCE(s.estimativa_tempo, o.estimativa_tempo) AS estimativa_tempo
                          FROM sessao s 
                          JOIN projeto p ON s.id_projeto = p.id_projeto 
                          JOIN usuario u ON p.id_usuario = u.id_usuario 
                          LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento
                          WHERE s.status = 'Agendado' AND s.data_hora >= NOW() 
                          ORDER BY s.data_hora ASC LIMIT 5";
    $proximas_sessoes = $pdo->query($sql_sessoes_lista)->fetchAll();
} catch (PDOException $e) {
    $qtd_sessoes_semana = 0;
    $qtd_novos_clientes = 0;
    $proximas_sessoes = [];
}

include '../includes/header.php';
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
    }
    echo '</div>';
}
?>

<style>
    .accordion-button:not(.collapsed) {
        background-color: #333 !important;
        color: #fff !important;
        box-shadow: none !important;
    }

    .accordion-body {
        background-color: #333 !important;
    }

    .accordion-button:focus {
        box-shadow: none !important;
    }

    .accordion-item {
        border-color: #444 !important;
    }
</style>

<main>
    <div class="container my-5 py-5">
        <h2 class="text-center mb-5">PAINEL DE CONTROLE</h2>

        <?php if (isset($_GET['sucesso'])): ?>
            <?php if ($_GET['sucesso'] == 'proposta_enviada'): ?>
                <div class="alert alert-success text-center mb-4 alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i> Orçamento aprovado! A proposta foi enviada ao cliente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['sucesso'] == 'recusado'): ?>
                <div class="alert alert-warning text-center mb-4 alert-dismissible fade show" role="alert">
                    <i class="bi bi-x-circle me-2"></i> O orçamento foi recusado.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['sucesso'] == 'reagendado'): ?>
                <div class="alert alert-info text-center mb-4 alert-dismissible fade show" role="alert">
                    <i class="bi bi-calendar-event me-2"></i> Reagendamento solicitado com sucesso! O cliente foi notificado.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['sucesso'] == 'cancelado'): ?>
                <div class="alert alert-success text-center mb-4 alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i> Projeto cancelado com sucesso.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['erro']) && $_GET['erro'] == 'bd'): ?>
            <div class="alert alert-danger text-center mb-4 alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i> Erro ao processar. Verifique se preencheu tudo corretamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <style>
            .card-hover {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .card-hover:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 15px rgba(255, 255, 255, 0.1);
                border-color: #ffffff !important;
                cursor: pointer;
            }
        </style>

        <div class="row text-center">
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="agenda.php?aba=solicitacoes" class="card-resumo card-hover text-decoration-none text-light d-block h-100">
                    <h3><?php echo $qtd_pendentes; ?></h3>
                    <p class="text-white-50 mb-0">Para Aprovar</p>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="agenda.php?aba=enviadas" class="card-resumo card-hover text-decoration-none text-light d-block h-100">
                    <h3><?php echo $qtd_enviadas; ?></h3>
                    <p class="text-white-50 mb-0">Propostas Enviadas</p>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="agenda.php?aba=sessoes" class="card-resumo card-hover text-decoration-none text-light d-block h-100">
                    <h3><?php echo $qtd_sessoes_semana; ?></h3>
                    <p class="text-white-50 mb-0">Sessões na Semana</p>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="relatorios-artista.php?aba=clientes" class="card-resumo card-hover text-decoration-none text-light d-block h-100">
                    <h3><?php echo $qtd_novos_clientes; ?></h3>
                    <p class="text-white-50 mb-0">Novos Clientes no Mês</p>
                </a>
            </div>
        </div>

        <hr class="my-5 border-secondary">

        <div class="row">
            <div class="col-lg-6 mb-4">
                <h4 class="mb-4">Solicitações Pendentes</h4>

                <?php if (empty($solicitacoes_pendentes)): ?>
                    <div class="card-resumo text-center text-white-50 mb-0">
                        Nenhuma solicitação pendente no momento.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoSolicitacoes">
                        <?php foreach ($solicitacoes_pendentes as $i => $req): ?>
                            <div class="accordion-item bg-dark border-secondary mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed text-light" type="button" data-bs-toggle="collapse" data-bs-target="#req-<?php echo $i; ?>">
                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                            <span><strong>Cliente:</strong> <?php echo htmlspecialchars($req['nome_cliente']); ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="req-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoSolicitacoes">
                                    <div class="accordion-body text-white-50">
                                        <p><strong>Local do Corpo:</strong> <?php echo htmlspecialchars($req['local_corpo']); ?></p>
                                        <p><strong>Tamanho Aproximado:</strong> <?php echo htmlspecialchars($req['tamanho_aproximado']); ?></p>
                                        <p><strong>Ideia do Cliente:</strong> "<?php echo htmlspecialchars($req['descricao_ideia']); ?>"</p>
                                        <p><strong>Referência Enviada:</strong>
                                            <?php if (!empty($req['referencia_ideia'])): ?>
                                                <a href="../imagens/orcamentos/<?php echo $req['referencia_ideia']; ?>" target="_blank" class="text-info text-decoration-none"><i class="bi bi-image me-1"></i>Ver Anexo</a>
                                            <?php else: ?>
                                                Vazio
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($req['status'] == 'Negociacao'): ?>
                                            <hr class="my-3 border-secondary">
                                            <div class="alert alert-warning p-2 small mb-0">
                                                <strong>⚠️</strong> O cliente achou o valor alto e pediu uma revisão.
                                                <br><strong>Sua oferta anterior:</strong> R$ <?php echo htmlspecialchars($req['valor_sessao'] ?? ''); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-end align-items-center mt-4">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-recusar" data-id="<?php echo $req['id_orcamento']; ?>" data-bs-toggle="modal" data-bs-target="#modalRecusar">Recusar</button>

                                            <button type="button" class="btn btn-sm btn-success ms-2 btn-aprovar"
                                                data-id="<?php echo $req['id_orcamento']; ?>"
                                                data-titulo="<?php echo htmlspecialchars($req['titulo_sugerido'] ?? ''); ?>"
                                                data-tempo="<?php echo htmlspecialchars($req['estimativa_tempo'] ?? ''); ?>"
                                                data-sessoes="<?php echo htmlspecialchars($req['qtd_sessoes'] ?? ''); ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalAprovar">
                                                Enviar Proposta
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="agenda.php?aba=solicitacoes" class="btn btn-outline-secondary px-4 py-2" style="text-transform: uppercase;"> Ver Todas as Solicitações</a>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <h4 class="mb-4">Próximas Sessões</h4>

                <?php if (empty($proximas_sessoes)): ?>
                    <div class="card-resumo text-center text-white-50 mb-0">
                        Nenhuma sessão agendada para os próximos dias.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoSessoesAgendadas">
                        <?php foreach ($proximas_sessoes as $i => $sessao):
                            $data_sessao = new DateTime($sessao['data_hora']);
                        ?>
                            <div class="accordion-item bg-dark border-secondary mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed text-light" type="button" data-bs-toggle="collapse" data-bs-target="#sessao-<?php echo $i; ?>">
                                        <div class="w-100 d-flex flex-column">
                                            <div class="d-flex justify-content-between w-100">
                                                <span><strong>Projeto:</strong> <?php echo htmlspecialchars($sessao['titulo']); ?></span>
                                                <span class="me-3 text-light"><i class="bi bi-calendar3 me-1"></i> <?php echo $data_sessao->format('d/m/Y - H:i'); ?></span>
                                            </div>
                                            <span class="mt-1 small text-white-50"><strong>Cliente:</strong> <?php echo htmlspecialchars($sessao['nome_cliente']); ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="sessao-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                                    <div class="accordion-body text-white-50">
                                        <p class="mb-1"><strong>Local do Corpo:</strong> <?php echo htmlspecialchars($sessao['local_corpo'] ?? 'Não informado'); ?></p>
                                        <p class="mb-1"><strong>Ideia do Cliente:</strong> "<?php echo htmlspecialchars($sessao['descricao_ideia'] ?? 'Não informada'); ?>"</p>
                                        <p class="mb-3"><strong>Referência Enviada:</strong>
                                            <?php if (!empty($sessao['referencia_ideia'])): ?>
                                                <a href="../imagens/orcamentos/<?php echo $sessao['referencia_ideia']; ?>" target="_blank" class="text-info text-decoration-none"><i class="bi bi-image me-1"></i>Ver Anexo</a>
                                            <?php else: ?>
                                                Vazio
                                            <?php endif; ?>
                                        </p>

                                        <p class="mb-1 mt-3"><strong>Duração Estimada:</strong> <?php echo htmlspecialchars($sessao['estimativa_tempo'] ?? 'A definir'); ?></p>
                                        <p class="mb-1"><strong>Sessões Estimadas:</strong> <?php echo htmlspecialchars($sessao['qtd_sessoes'] ?? '-'); ?></p>
                                        <p class="mb-3"><strong>Valor da Sessão:</strong> R$ <?php echo !empty($sessao['valor_sessao']) ? number_format($sessao['valor_sessao'], 2, ',', '.') : 'Não definido'; ?></p>

                                        <div class="d-flex justify-content-end mt-3 gap-2 border-top border-secondary pt-3 flex-wrap">
                                            <button class="btn btn-sm btn-success btn-concluir-sessao-js" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalConfirmarConcluir">
                                                <i class="bi bi-check-lg me-1"></i>Concluído
                                            </button>
                                            <button class="btn btn-sm btn-outline-info btn-liberar-sessao-js"
                                                data-idproj="<?php echo $sessao['id_projeto']; ?>"
                                                data-valor="<?php echo htmlspecialchars($sessao['valor_sessao'] ?? ''); ?>"
                                                data-tempo="<?php echo htmlspecialchars($sessao['estimativa_tempo'] ?? ''); ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalConfirmarLiberar">
                                                <i class="bi bi-unlock me-1"></i>Liberar Nova Sessão
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning btn-reagendar-sessao" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalReagendarArtista">Reagendar</button>
                                            <button class="btn btn-sm btn-outline-danger btn-cancelar-sessao" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelarSessao">Cancelar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="agenda.php?aba=sessoes" class="btn btn-outline-secondary px-4 py-2" style="text-transform: uppercase;"> Ver Todas as Sessões</a>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalAprovar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Enviar Proposta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <form action="../actions/a.aprovar-orcamento.php" method="POST">
                    <input type="hidden" name="orcamento_id" id="inputAprovarId" value="">
                    <input type="hidden" name="origem" value="<?php echo basename($_SERVER['PHP_SELF']); ?>">

                    <div class="mb-3">
                        <label for="titulo_projeto" class="form-label text-light">Título do Projeto:</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="titulo_projeto" name="titulo_projeto" placeholder="" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light">Valor da Sessão:</label>

                        <div id="aviso_renegociacao" class="alert alert-info p-2 small mb-2 text-center fw-bold" style="display: none;">
                            <i class="bi bi-info-circle me-1"></i> Informe um novo valor ou repita o valor anterior.
                        </div>

                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white-50 border-secondary border-end-0">R$</span>
                            <input type="text" class="form-control bg-dark text-light border-secondary border-start-0 mascara-dinheiro" id="input_valor_destaque" name="valor_sessao" placeholder="0,00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="estimativa_tempo" class="form-label text-light">Estimativa de Tempo (por sessão):</label>
                        <select class="form-select bg-dark text-light border-secondary" id="estimativa_tempo" name="estimativa_tempo" required>
                            <option value="" selected disabled></option>
                            <option value="Projeto Pequeno (até 2h)">Projeto Pequeno (até 2h)</option>
                            <option value="Projeto Médio (2h a 4h)">Projeto Médio (2h a 4h)</option>
                            <option value="Projeto Grande (5h a 6h)">Projeto Grande (5h a 6h)</option>
                            <option value="Fechamento (dia todo)">Fechamento (dia todo)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="qtd_sessoes" class="form-label text-light">Estimativa de Sessões Necessárias:</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="qtd_sessoes" name="qtd_sessoes" min="1" max="20" placeholder="" required>
                        <div class="form-text text-white-50">O cliente receberá a proposta e decidirá se aceita o valor para agendar.</div>
                    </div>

                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-success">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRecusar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Recusar Projeto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <form action="../actions/a.recusar-orcamento.php" method="POST">
                    <input type="hidden" name="orcamento_id" id="inputRecusarId" value="">
                    <input type="hidden" name="origem" value="<?php echo basename($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="motivo_recusa" class="form-label text-light">Motivo:</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="motivo_recusa" name="motivo_recusa" rows="3" placeholder="" required></textarea>
                    </div>
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Recusar Projeto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCancelarSessao" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Cancelar Projeto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <form action="../actions/a.cancelar-projeto.php" method="POST">
                    <input type="hidden" name="sessao_id" id="inputSessaoIdArtista" value="">
                    <input type="hidden" name="origem" value="<?php echo basename($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="motivo_cancelamento" class="form-label text-light">Motivo:</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="motivo_cancelamento" name="motivo" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReagendarArtista" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-warning">Solicitar Reagendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Solicitar que o cliente agende uma nova data/horário.</p>
                <form action="../actions/a.reagendar-artista.php" method="POST">
                    <input type="hidden" name="sessao_id" id="inputReagendarIdArtista" value="">
                    <input type="hidden" name="origem" value="<?php echo basename($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label class="form-label text-light">Motivo:</label>
                        <textarea class="form-control bg-dark text-light border-secondary" name="motivo" rows="2" placeholder="" required></textarea>
                    </div>
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-warning">Solicitar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmarConcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark border-secondary">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-success">Concluir Projeto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Tem certeza que deseja <strong>finalizar</strong> este projeto?</p>
                <p class="small">A sessão será dada como concluída e o projeto irá direto para o seu histórico.</p>
                <form action="../actions/a.concluir-sessao.php" method="POST">
                    <input type="hidden" name="sessao_id" id="inputConfirmarConcluirId" value="">
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-success">Concluir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmarLiberar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark border-secondary">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-info">Liberar Nova Sessão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Revise os detalhes para a <strong>próxima sessão</strong> antes de liberar para o cliente.</p>
                <form action="../actions/a.liberar-sessao.php" method="POST">
                    <input type="hidden" name="projeto_id" id="inputConfirmarLiberarId" value="">
                    <div class="mb-3">
                        <label class="form-label text-light">Valor da Próxima Sessão:</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white-50 border-secondary border-end-0">R$</span>
                            <input type="text" class="form-control bg-dark text-light border-secondary border-start-0 mascara-dinheiro" id="inputLiberarValor" name="valor_sessao" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="inputLiberarTempo" class="form-label text-light">Duração da Próxima Sessão:</label>
                        <select class="form-select bg-dark text-light border-secondary" id="inputLiberarTempo" name="estimativa_tempo" required>
                            <option value="" disabled></option>
                            <option value="Projeto Pequeno (Até 2h)">Projeto Pequeno (Até 2h)</option>
                            <option value="Projeto Médio (2h a 4h)">Projeto Médio (2h a 4h)</option>
                            <option value="Projeto Grande (5h a 6h)">Projeto Grande (5h a 6h)</option>
                            <option value="Fechamento (Dia Todo)">Fechamento (dia todo)</option>
                        </select>
                    </div>
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-info text-white">Liberar Agendamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // máscara inteligente para o Valor da Sessão
        const inputsDinheiro = document.querySelectorAll('.mascara-dinheiro');
        inputsDinheiro.forEach(input => {
            input.addEventListener('input', function(e) {
                let valor = e.target.value;

                // Remove tudo que não for número
                valor = valor.replace(/\D/g, "");

                // Se estiver vazio, limpa o campo
                if (valor === "") {
                    e.target.value = "";
                    return;
                }

                // Converte para float e divide por 100 para criar os decimais (centavos)
                valor = (parseInt(valor, 10) / 100).toFixed(2);

                // Troca o ponto por vírgula e aplica os pontos de milhar
                valor = valor.replace(".", ",");
                valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");

                e.target.value = valor;
            });
        });

        // --- LÓGICA DE APROVAR ORÇAMENTO ---
        const btnsAprovar = document.querySelectorAll('.btn-aprovar');
        const inputAprovarId = document.getElementById('inputAprovarId');
        const inputTituloProjeto = document.getElementById('titulo_projeto');
        const selectEstimativaTempo = document.getElementById('estimativa_tempo');
        const inputQtdSessoes = document.getElementById('qtd_sessoes');

        const avisoRenegociacao = document.getElementById('aviso_renegociacao');
        const inputValorDestaque = document.getElementById('input_valor_destaque');

        btnsAprovar.forEach(btn => {
            btn.addEventListener('click', function() {
                inputAprovarId.value = this.getAttribute('data-id');

                // Preenche os campos
                const tituloAntigo = this.getAttribute('data-titulo') || '';
                inputTituloProjeto.value = tituloAntigo;
                inputQtdSessoes.value = this.getAttribute('data-sessoes') || '';

                const tempoValue = this.getAttribute('data-tempo');
                if (tempoValue) {
                    selectEstimativaTempo.value = tempoValue;
                } else {
                    selectEstimativaTempo.value = '';
                }

                inputValorDestaque.value = '';

                // Se tiver um título antigo, é RENEGOCIAÇÃO 
                if (tituloAntigo !== '') {
                    avisoRenegociacao.style.display = 'block';
                    inputValorDestaque.classList.remove('border-secondary');
                    inputValorDestaque.style.borderColor = '#ffffff'; // Borda branca
                    inputValorDestaque.style.boxShadow = '0 0 10px rgba(255, 255, 255, 0.2)'; // Sombra branca suave
                } else {
                    avisoRenegociacao.style.display = 'none';
                    inputValorDestaque.classList.add('border-secondary');
                    inputValorDestaque.classList.remove('border-info');
                    inputValorDestaque.style.boxShadow = 'none';
                }
            });
        });

        // recusar orçamento
        const btnsRecusar = document.querySelectorAll('.btn-recusar');
        const inputRecusarId = document.getElementById('inputRecusarId');
        btnsRecusar.forEach(btn => {
            btn.addEventListener('click', function() {
                inputRecusarId.value = this.getAttribute('data-id');
            });
        });

        // cancelamento de sessao
        const btnsCancelarSessao = document.querySelectorAll('.btn-cancelar-sessao');
        const inputSessaoIdArtista = document.getElementById('inputSessaoIdArtista');
        btnsCancelarSessao.forEach(btn => {
            btn.addEventListener('click', function() {
                inputSessaoIdArtista.value = this.getAttribute('data-id');
            });
        });

        // modais de concluir / liberar sessao
        const btnsConcluirSessao = document.querySelectorAll('.btn-concluir-sessao-js');
        const inputConfirmarConcluirId = document.getElementById('inputConfirmarConcluirId');
        btnsConcluirSessao.forEach(btn => {
            btn.addEventListener('click', function() {
                inputConfirmarConcluirId.value = this.getAttribute('data-id');
            });
        });

        const btnsLiberarSessao = document.querySelectorAll('.btn-liberar-sessao-js');
        const inputConfirmarLiberarId = document.getElementById('inputConfirmarLiberarId');
        const inputLiberarValor = document.getElementById('inputLiberarValor');
        const inputLiberarTempo = document.getElementById('inputLiberarTempo');
        btnsLiberarSessao.forEach(btn => {
            btn.addEventListener('click', function() {
                inputConfirmarLiberarId.value = this.getAttribute('data-idproj');
                inputLiberarValor.value = this.getAttribute('data-valor');
                const tempo = this.getAttribute('data-tempo');
                if (tempo) inputLiberarTempo.value = tempo;
            });
        });

        // reagendar sessao
        const btnsReagendarSessao = document.querySelectorAll('.btn-reagendar-sessao');
        const inputReagendarIdArtista = document.getElementById('inputReagendarIdArtista');
        btnsReagendarSessao.forEach(btn => {
            btn.addEventListener('click', function() {
                inputReagendarIdArtista.value = this.getAttribute('data-id');
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>