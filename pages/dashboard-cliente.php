<?php
session_start();
require_once '../includes/conexao.php'; // conexao com o Clever Cloud

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// variaveis necessarias 
$id_usuario = $_SESSION['usuario_id'];
$titulo_pagina = "Meu Painel";

// consulta SQL pra localizar os 3 agendamentos mais proximos
try {
    $sql = "SELECT s.id_sessao, s.data_hora, p.titulo, p.id_projeto, o.local_corpo, o.tamanho_aproximado, o.descricao_ideia, 
                   COALESCE(s.estimativa_tempo, o.estimativa_tempo) AS estimativa_tempo, 
                   o.referencia_ideia, o.qtd_sessoes, 
                   COALESCE(s.valor_sessao, o.valor_sessao) AS valor_sessao 
            FROM sessao s
            JOIN projeto p ON s.id_projeto = p.id_projeto
            LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento
            WHERE p.id_usuario = ? 
            AND s.data_hora >= NOW() 
            AND s.status = 'Agendado'
            ORDER BY s.data_hora ASC 
            LIMIT 5";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_usuario]);
    $proximos_agendamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $proximos_agendamentos = [];
}

try {
    $stmt_analise = $pdo->prepare("SELECT COUNT(*) FROM orcamento WHERE id_usuario = ? AND (status IN ('Pendente', 'Negociacao') OR status IS NULL)");
    $stmt_analise->execute([$id_usuario]);
    $qtd_orcamentos = $stmt_analise->fetchColumn();

    $stmt_acao1 = $pdo->prepare("SELECT COUNT(*) FROM orcamento WHERE id_usuario = ? AND status = 'Aguardando Aceite'");
    $stmt_acao1->execute([$id_usuario]);

    $stmt_acao2 = $pdo->prepare("SELECT COUNT(*) FROM projeto WHERE id_usuario = ? AND status = 'Agendamento Pendente'");
    $stmt_acao2->execute([$id_usuario]);
    $qtd_acoes_requeridas = $stmt_acao1->fetchColumn() + $stmt_acao2->fetchColumn();

    $stmt_sessoes = $pdo->prepare("SELECT COUNT(*) FROM sessao s JOIN projeto p ON s.id_projeto = p.id_projeto WHERE p.id_usuario = ? AND s.status = 'Agendado' AND s.data_hora >= NOW()");
    $stmt_sessoes->execute([$id_usuario]);
    $qtd_sessoes = $stmt_sessoes->fetchColumn();
} catch (PDOException $e) {
    $qtd_orcamentos = 0;
    $qtd_acoes_requeridas = 0;
    $qtd_sessoes = 0;
}

include '../includes/header.php';
?>

<?php
// menu lateral
$pagina_ativa = basename($_SERVER['PHP_SELF']);
$link_prefix = '';

echo '<div class="submenu-painel">';

if ($_SESSION['usuario_perfil'] == 'artista') {
    echo '<a href="dashboard-artista.php">Início</a>';
    echo '<a href="agenda.php">Agenda</a>';
    echo '<a href="portfolio-artista.php">Portfólio</a>';
    echo '<a href="relatorios-artista.php">Relatórios</a>';
    echo '<a href="configuracoes-artista.php">Configurações</a>';
} else {
    echo '<a href="dashboard-cliente.php" class="active">Início</a>';
    echo '<a href="agendamentos-cliente.php">Meus Agendamentos</a>';
    echo '<a href="solicitar-orcamento.php">Orçamento</a>';
    echo '<a href="configuracoes-cliente.php">Configurações</a>';
}
echo '</div>';
?>

<main>
    <div class="container my-5 py-5">
        <h2 class="text-center mb-4">BEM-VINDO, <?php echo strtoupper($_SESSION['usuario_nome']); ?></h2>

        <?php if (isset($_GET['sucesso'])): ?>
            <?php if ($_GET['sucesso'] == 'cancelado'): ?>
                <div class="alert alert-success alert-dismissible fade show text-center mb-5" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Projeto cancelado.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['sucesso'] == 'reagendado'): ?>
                <div class="alert alert-warning alert-dismissible fade show text-center mb-5" role="alert">
                    <i class="bi bi-calendar-x me-2"></i> Sessão cancelada. Seu projeto foi movido para a fila de reagendamento!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($_GET['erro']) && $_GET['erro'] == 'bd'): ?>
            <div class="alert alert-danger alert-dismissible fade show text-center mb-5" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Ocorreu um erro ao processar sua solicitação no banco de dados. Tente novamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <style>
            .accordion-button:not(.collapsed) {
                background-color: transparent !important;
                color: #fff !important;
                box-shadow: none !important;
            }

            .accordion-button:focus {
                box-shadow: none !important;
            }

            .accordion-item {
                border-color: #444 !important;
                background-color: #2c2c2c !important;
            }

            .accordion-button,
            .accordion-body {
                background-color: transparent !important;
            }

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

        <div class="row text-center mb-5">
            <div class="col-md-4 mb-4">
                <a href="agendamentos-cliente.php#acordeaoAcaoRequerida" class="card-resumo card-hover text-decoration-none text-light d-block">
                    <h3><?php echo $qtd_acoes_requeridas; ?></h3>
                    <p class="text-white-50 mb-0">Ações Requeridas</p>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="agendamentos-cliente.php?aba=proximas" class="card-resumo card-hover text-decoration-none text-light d-block">
                    <h3><?php echo $qtd_sessoes; ?></h3>
                    <p class="text-white-50 mb-0">Sessões Agendadas</p>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="agendamentos-cliente.php?aba=analise" class="card-resumo card-hover text-decoration-none text-light d-block">
                    <h3><?php echo $qtd_orcamentos; ?></h3>
                    <p class="text-white-50 mb-0">Orçamentos em Análise</p>
                </a>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <h3 class="mb-4">Próximos Agendamentos</h3>

                <?php if (empty($proximos_agendamentos)): ?>
                    <div class="card-resumo mb-3 text-center">
                        <p class="text-white-50 mb-0 py-3">Você ainda não possui sessões agendadas.</p>
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoProximosDash">
                        <?php foreach ($proximos_agendamentos as $i => $sessao):
                            $data = new DateTime($sessao['data_hora']);
                        ?>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed text-light" type="button" data-bs-toggle="collapse" data-bs-target="#sessao-dash-<?php echo $i; ?>">
                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                            <span><strong>Projeto:</strong> <?php echo htmlspecialchars($sessao['titulo']); ?></span>
                                            <span class="me-3 text-light small"><i class="bi bi-calendar3 me-1"></i> <?php echo $data->format('d/m/Y - H:i'); ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="sessao-dash-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoProximosDash">
                                    <div class="accordion-body text-white-50">
                                        <div class="small mb-3">
                                            <p class="mb-1"><strong>Local:</strong> <?php echo htmlspecialchars($sessao['local_corpo'] ?? 'Não informado'); ?></p>
                                            <p class="mb-1"><strong>Ideia:</strong> "<?php echo htmlspecialchars($sessao['descricao_ideia'] ?? 'Não informada'); ?>"</p>
                                            <p class="mb-1"><strong>Duração:</strong> <?php echo htmlspecialchars($sessao['estimativa_tempo'] ?? 'A definir'); ?></p>
                                            <p class="mb-1"><strong>Sessões Estimadas:</strong> <?php echo htmlspecialchars($sessao['qtd_sessoes'] ?? '-'); ?></p>
                                            <p class="mb-1"><strong>Valor da Sessão:</strong> R$ <?php echo !empty($sessao['valor_sessao']) ? number_format($sessao['valor_sessao'], 2, ',', '.') : 'Não definido'; ?></p>
                                            <p class="mb-0"><strong>Referência:</strong>
                                                <?php if (!empty($sessao['referencia_ideia'])): ?>
                                                    <a href="../imagens/orcamentos/<?php echo htmlspecialchars($sessao['referencia_ideia']); ?>" target="_blank" class="text-info text-decoration-none">
                                                        <i class="bi bi-image me-1"></i> Ver Anexo
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-white-50">Vazio</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="text-end border-top border-secondary pt-3">
                                            <button class="btn btn-sm btn-outline-warning btn-reagendar-js" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalReagendar">Reagendar</button>

                                            <button class="btn btn-sm btn-outline-danger btn-cancelar-js ms-2" data-id="<?php echo $sessao['id_sessao']; ?>" data-projeto-id="<?php echo $sessao['id_projeto']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelarProjeto">Cancelar Projeto</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <h3 class="mb-4 invisible">Ações</h3>
                <div class="card-resumo p-4">
                    <p class="text-white-50 mb-4">Acompanhe seus agendamentos ou solicite um novo orçamento.</p>
                    <div class="d-grid gap-2">
                        <a href="solicitar-orcamento.php" class="btn btn-primary">SOLICITAR ORÇAMENTO</a>
                        <a href="agendamentos-cliente.php" class="btn btn-outline-light">VER TODOS OS AGENDAMENTOS</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="modalReagendar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark border-secondary">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Reagendar Sessão</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>A data atual será desmarcada e você será levado para o calendário para escolher um novo dia.</p>
                <form action="../actions/a.reagendar.php" method="POST">
                    <input type="hidden" name="sessao_id" id="inputReagendarId" value="">
                    <input type="hidden" name="origem" value="dashboard-cliente.php">
                    <div class="mb-3">
                        <label class="form-label text-light">Motivo do reagendamento:</label>
                        <textarea class="form-control bg-dark text-light border-secondary" name="motivo" rows="2" required></textarea>
                    </div>
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-warning">Reagendar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCancelarProjeto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark border-secondary">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-danger">Cancelar Projeto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Atenção: Isso cancelará não apenas esta sessão, mas o <strong>projeto inteiro</strong>.</p>
                <form action="../actions/a.cancelar-projeto.php" method="POST">
                    <input type="hidden" name="sessao_id" id="inputCancelarProjetoSessaoId" value="">
                    <input type="hidden" name="projeto_id" id="inputCancelarProjetoId" value="">
                    <input type="hidden" name="origem" value="dashboard-cliente.php">
                    <div class="mb-3">
                        <label class="form-label text-light">Motivo:</label>
                        <textarea class="form-control bg-dark text-light border-secondary" name="motivo" rows="2" required></textarea>
                    </div>
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // conecta o botão Reagendar ao Modal
        const btnsReagendar = document.querySelectorAll('.btn-reagendar-js');
        const inputReagendarId = document.getElementById('inputReagendarId');
        btnsReagendar.forEach(btn => {
            btn.addEventListener('click', function() {
                inputReagendarId.value = this.getAttribute('data-id');
            });
        });

        // conecta o botão Cancelar aos DOIS inputs do Modal
        const btnsCancelar = document.querySelectorAll('.btn-cancelar-js');
        const inputCancelarProjetoSessaoId = document.getElementById('inputCancelarProjetoSessaoId');
        const inputCancelarProjetoId = document.getElementById('inputCancelarProjetoId');
        btnsCancelar.forEach(btn => {
            btn.addEventListener('click', function() {
                inputCancelarProjetoSessaoId.value = this.getAttribute('data-id');
                inputCancelarProjetoId.value = this.getAttribute('data-projeto-id');
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>