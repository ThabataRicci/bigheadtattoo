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
                            ORDER BY o.data_envio ASC";
    $solicitacoes_pendentes = $pdo->query($sql_pendentes_lista)->fetchAll();
} catch (PDOException $e) {
    $qtd_pendentes = 0;
    $solicitacoes_pendentes = [];
}

// 2. busca sessoes e clientes
try {
    $stmt_sessoes = $pdo->query("SELECT COUNT(*) FROM sessao WHERE status = 'Agendado' AND data_hora BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)");
    $qtd_sessoes_semana = $stmt_sessoes->fetchColumn();

    $stmt_clientes = $pdo->query("SELECT COUNT(*) FROM usuario WHERE perfil = 'cliente' AND MONTH(data_cadastro) = MONTH(NOW()) AND YEAR(data_cadastro) = YEAR(NOW())");
    $qtd_novos_clientes = $stmt_clientes->fetchColumn();

    $sql_sessoes_lista = "SELECT s.id_sessao, s.data_hora, p.titulo, u.nome AS nome_cliente, 
                                 o.descricao_ideia, o.local_corpo, o.referencia_ideia
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
                    <i class="bi bi-x-circle me-2"></i> O orçamento foi recusado e o cliente notificado.
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
                box-shadow: 0 4px 15px rgba(7, 94, 255, 0.2);
                border-color: #0dcaf0 !important;
                cursor: pointer;
            }
        </style>

        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <a href="agenda.php?aba=solicitacoes" class="text-decoration-none text-light">
                    <div class="card-resumo card-hover">
                        <h3><?php echo $qtd_pendentes; ?></h3>
                        <p class="text-white-50 mb-0">Solicitações para Aprovar</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="agenda.php?aba=sessoes" class="text-decoration-none text-light">
                    <div class="card-resumo card-hover">
                        <h3><?php echo $qtd_sessoes_semana; ?></h3>
                        <p class="text-white-50 mb-0">Sessões na Semana</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="relatorios-artista.php" class="text-decoration-none text-light">
                    <div class="card-resumo card-hover">
                        <h3><?php echo $qtd_novos_clientes; ?></h3>
                        <p class="text-white-50 mb-0">Novos Clientes no Mês</p>
                    </div>
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

                                            <button type="button" class="btn btn-sm btn-success ms-2 btn-aprovar" data-id="<?php echo $req['id_orcamento']; ?>" data-bs-toggle="modal" data-bs-target="#modalAprovar">Enviar Proposta</button>
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

                                        <hr class="my-3 border-secondary">

                                        <div class="text-end mt-2">
                                            <button class="btn btn-sm btn-outline-warning btn-reagendar-sessao me-2" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalReagendarArtista">Reagendar</button>
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
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white-50 border-secondary border-end-0">R$</span>
                            <input type="text" class="form-control bg-dark text-light border-secondary border-start-0 mascara-dinheiro" name="valor_sessao" placeholder="0,00" required>
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
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="qtd_sessoes" name="qtd_sessoes" min="1" max="20" placeholder="Ex: 1" required>
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
                        <label for="motivo_cancelamento" class="form-label text-light">Motivo:</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="motivo_cancelamento" name="motivo_cancelamento" rows="3" required></textarea>
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
                        <textarea class="form-control bg-dark text-light border-secondary" name="motivo" rows="2" placeholder="Ex: Tive um imprevisto, peço que remarque para a próxima semana..." required></textarea>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Máscara inteligente para o Valor da Sessão (Dinheiro)
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