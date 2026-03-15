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

// 1. busca os orçamentos pendentes
try {
    $stmt_pendentes = $pdo->query("SELECT COUNT(*) FROM orcamento WHERE status = 'Pendente' OR status IS NULL");
    $qtd_pendentes = $stmt_pendentes->fetchColumn();

    $sql_pendentes_lista = "SELECT o.*, u.nome AS nome_cliente 
                            FROM orcamento o 
                            JOIN usuario u ON o.id_usuario = u.id_usuario 
                            WHERE o.status = 'Pendente' OR o.status IS NULL 
                            ORDER BY o.data_envio ASC";
    $solicitacoes_pendentes = $pdo->query($sql_pendentes_lista)->fetchAll();
} catch (PDOException $e) {
    $qtd_pendentes = 0;
    $solicitacoes_pendentes = [];
}

// 2. busca sessoes e clientes
try {
    $stmt_sessoes = $pdo->query("SELECT COUNT(*) FROM sessao WHERE status = 'Agendada' AND data_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)");
    $qtd_sessoes_semana = $stmt_sessoes->fetchColumn();

    $stmt_clientes = $pdo->query("SELECT COUNT(*) FROM usuario WHERE perfil = 'cliente' AND MONTH(data_cadastro) = MONTH(NOW()) AND YEAR(data_cadastro) = YEAR(NOW())");
    $qtd_novos_clientes = $stmt_clientes->fetchColumn();

    $sql_sessoes_lista = "SELECT s.id_sessao, s.data_hora, p.titulo, u.nome AS nome_cliente 
                          FROM sessao s 
                          JOIN projeto p ON s.id_projeto = p.id_projeto 
                          JOIN usuario u ON p.id_usuario = u.id_usuario 
                          WHERE s.status = 'Agendada' AND s.data_hora >= NOW() 
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

        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="card-resumo">
                    <h3><?php echo $qtd_pendentes; ?></h3>
                    <p class="text-white-50 mb-0">Solicitações para Aprovar</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-resumo">
                    <h3><?php echo $qtd_sessoes_semana; ?></h3>
                    <p class="text-white-50 mb-0">Sessões na Semana</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card-resumo">
                    <h3><?php echo $qtd_novos_clientes; ?></h3>
                    <p class="text-white-50 mb-0">Novos Clientes no Mês</p>
                </div>
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
                                        <div class="d-flex justify-content-end align-items-center mt-4">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-recusar" data-id="<?php echo $req['id_orcamento']; ?>" data-bs-toggle="modal" data-bs-target="#modalRecusar">Recusar</button>

                                            <button type="button" class="btn btn-sm btn-success ms-2 btn-aprovar" data-id="<?php echo $req['id_orcamento']; ?>" data-bs-toggle="modal" data-bs-target="#modalAprovar">Aprovar Projeto</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                                                <span class="me-3 text-info"><strong>Data:</strong> <?php echo $data_sessao->format('d/m/Y \à\s H:i'); ?></span>
                                            </div>
                                            <span class="mt-1 small text-white-50"><strong>Cliente:</strong> <?php echo htmlspecialchars($sessao['nome_cliente']); ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="sessao-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                                    <div class="accordion-body text-white-50">
                                        <div class="text-end mt-2">
                                            <button class="btn btn-sm btn-outline-danger btn-cancelar-sessao" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelarSessao">Cancelar Sessão</button>
                                        </div>
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

<div class="modal fade" id="modalAprovar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Aprovar Projeto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <form action="../actions/a.aprovar-orcamento.php" method="POST">
                    <input type="hidden" name="orcamento_id" id="inputAprovarId" value="">

                    <div class="mb-3">
                        <label for="estimativa_tempo" class="form-label text-light">Estimativa de Tempo (por sessão):</label>
                        <select class="form-select bg-dark text-light border-secondary" id="estimativa_tempo" name="estimativa_tempo" required>
                            <option value="" selected disabled></option>
                            <option value="Projeto Pequeno (Até 2h)">Projeto Pequeno (Até 2h)</option>
                            <option value="Projeto Médio (2h a 4h)">Projeto Médio (2h a 4h)</option>
                            <option value="Projeto Grande (5h a 6h)">Projeto Grande (5h a 6h)</option>
                            <option value="Fechamento (dia todo)">Fechamento (dia todo)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="qtd_sessoes" class="form-label text-light">Estimativa de Sessões Necessárias:</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="qtd_sessoes" name="qtd_sessoes" min="1" max="20" placeholder="Ex: 1" required>
                        <div class="form-text text-white-50">O cliente será notificado para realizar o agendamento.</div>
                    </div>

                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-success">Confirmar Aprovação</button>
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
                    <div class="mb-3">
                        <label for="motivo_recusa" class="form-label text-light">Motivo:</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="motivo_recusa" name="motivo_recusa" rows="3" placeholder="Ex: No momento não estou trabalhando com esse estilo..." required></textarea>
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
                <h5 class="modal-title">Cancelar Sessão Agendada</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <form action="../actions/a.cancelar-sessao.php" method="POST">
                    <input type="hidden" name="sessao_id" id="inputSessaoIdArtista" value="">
                    <div class="mb-3">
                        <label for="motivo_cancelamento" class="form-label text-light">Motivo (O cliente será avisado):</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="motivo_cancelamento" name="motivo_cancelamento" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
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
        // aprovar orçamento
        const btnsAprovar = document.querySelectorAll('.btn-aprovar');
        const inputAprovarId = document.getElementById('inputAprovarId');
        btnsAprovar.forEach(btn => {
            btn.addEventListener('click', function() {
                inputAprovarId.value = this.getAttribute('data-id');
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
    });
</script>

<?php include '../includes/footer.php'; ?>