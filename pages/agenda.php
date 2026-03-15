<?php
session_start();
require_once '../includes/conexao.php'; // Conecta ao banco de dados

$is_artista = (isset($_SESSION['loggedin']) && isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'artista');
$id_usuario_logado = $_SESSION['usuario_id'] ?? 0;

$ANO_VISUALIZACAO = 2025;
$ANO_ATIVO = 2025;
$MES_ATIVO = 10;

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

if ($ano < $ANO_VISUALIZACAO) {
    $ano = $ANO_VISUALIZACAO;
    $mes = $MES_ATIVO;
}
if ($ano == $ANO_VISUALIZACAO && $mes < $MES_ATIVO) {
    $mes = $MES_ATIVO;
}

// --- LÓGICA DE BANCO DE DADOS ---
$solicitacoes_pendentes = [];
$proximas_sessoes = [];
$dias_com_agendamento = [];
$todas_sessoes_array = [];

if ($is_artista) {
    try {
        // busca solicitações pendentes
        $sql_pendentes = "SELECT o.*, u.nome AS nome_cliente 
                          FROM orcamento o 
                          JOIN usuario u ON o.id_usuario = u.id_usuario 
                          WHERE o.status = 'Pendente' OR o.status IS NULL 
                          ORDER BY o.data_envio ASC";
        $solicitacoes_pendentes = $pdo->query($sql_pendentes)->fetchAll();

        // busca todas as sessões
        $sql_sessoes = "SELECT s.id_sessao, s.data_hora, s.status, p.titulo, u.nome AS nome_cliente 
                        FROM sessao s 
                        JOIN projeto p ON s.id_projeto = p.id_projeto 
                        JOIN usuario u ON p.id_usuario = u.id_usuario 
                        ORDER BY s.data_hora ASC";
        $todas_sessoes = $pdo->query($sql_sessoes)->fetchAll();

        foreach ($todas_sessoes as $s) {
            // separaa as sessoes agendadas e coloca no calendario
            if ($s['status'] === 'Agendada') {
                $proximas_sessoes[] = $s;
            }
            $data_apenas = date('Y-m-d', strtotime($s['data_hora']));
            if (!in_array($data_apenas, $dias_com_agendamento) && $s['status'] !== 'Cancelada') {
                $dias_com_agendamento[] = $data_apenas;
            }

            // organiza array por dia
            $todas_sessoes_array[$data_apenas][] = [
                'id' => $s['id_sessao'],
                'hora' => date('H:i', strtotime($s['data_hora'])),
                'titulo' => htmlspecialchars($s['titulo']),
                'cliente' => htmlspecialchars($s['nome_cliente']),
                'status' => $s['status']
            ];
        }
    } catch (PDOException $e) {
        // Silencioso
    }
}

// Dias fixos para o calendário
$dias_folga_semana = [0]; // Domingo
$dias_bloqueados_manualmente = ['2025-11-20', '2025-11-21']; // Pode vir de uma tabela futura
$dias_ocupados_total_cliente = array_merge($dias_com_agendamento, $dias_bloqueados_manualmente);

$primeiro_dia_timestamp = mktime(0, 0, 0, $mes, 1, $ano);
$total_dias_mes = date('t', $primeiro_dia_timestamp);
$primeiro_dia_semana = date('w', $primeiro_dia_timestamp);
$meses_pt = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];

$mes_anterior = $mes - 1;
$ano_anterior = $ano;
if ($mes_anterior == 0) {
    $mes_anterior = 12;
    $ano_anterior = $ano - 1;
}
if ($ano_anterior < $ANO_VISUALIZACAO || ($ano_anterior == $ANO_VISUALIZACAO && $mes_anterior < $MES_ATIVO)) {
    $mes_anterior = $MES_ATIVO;
    $ano_anterior = $ANO_VISUALIZACAO;
}

$mes_proximo = $mes + 1;
$ano_proximo = $ano;
if ($mes_proximo == 13) {
    $mes_proximo = 1;
    $ano_proximo = $ano + 1;
}

$projeto_id = $_GET['projeto_id'] ?? 0;
$tamanho = $_GET['tamanho'] ?? '';

$cliente_pode_agendar = true;
if (!$is_artista && ($projeto_id == 0 || $tamanho == '')) {
    $cliente_pode_agendar = false;
}

$titulo_pagina = $is_artista ? "Gerenciar Agenda" : "Escolha o Dia e Horário";

// DEFINIÇÃO ÚNICA DE "HOJE" (Simulação)
$hoje_dia = 7;
$hoje_mes = 11;
$hoje_ano = 2025;
$data_hoje_formatada = date('Y-m-d', mktime(0, 0, 0, $hoje_mes, $hoje_dia, $hoje_ano));

include '../includes/header.php';
?>

<?php
$pagina_ativa = basename($_SERVER['PHP_SELF']);

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true):
    if ($is_artista): ?>
        <div class="submenu-painel">
            <a href="dashboard-artista.php" class="<?php echo ($pagina_ativa == 'dashboard-artista.php') ? 'active' : ''; ?>">Início</a>
            <a href="agenda.php" class="<?php echo ($pagina_ativa == 'agenda.php') ? 'active' : ''; ?>">Agenda</a>
            <a href="portfolio-artista.php" class="<?php echo ($pagina_ativa == 'portfolio-artista.php') ? 'active' : ''; ?>">Portfólio</a>
            <a href="relatorios-artista.php" class="<?php echo ($pagina_ativa == 'relatorios-artista.php') ? 'active' : ''; ?>">Relatórios</a>
            <a href="configuracoes-artista.php" class="<?php echo ($pagina_ativa == 'configuracoes-artista.php') ? 'active' : ''; ?>">Configurações</a>
        </div>
    <?php else: ?>
        <div class="submenu-painel">
            <a href="dashboard-cliente.php" class="<?php echo ($pagina_ativa == 'dashboard-cliente.php') ? 'active' : ''; ?>">Início</a>
            <a href="agendamentos-cliente.php" class="<?php echo ($pagina_ativa == 'agendamentos-cliente.php') ? 'active' : ''; ?>">Meus Agendamentos</a>
            <a href="solicitar-orcamento.php" class="<?php echo ($pagina_ativa == 'solicitar-orcamento.php') ? 'active' : ''; ?>">Orçamento</a>
            <a href="configuracoes-cliente.php" class="<?php echo ($pagina_ativa == 'configuracoes-cliente.php') ? 'active' : ''; ?>">Configurações</a>
        </div>
<?php endif;
endif;
?>

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
    }

    .accordion-button,
    .accordion-body {
        background-color: transparent !important;
    }
</style>

<main>
    <div class="container my-5 py-5">

        <?php if ($is_artista || $cliente_pode_agendar): ?>

            <div class="text-center mb-5">
                <h2><?php echo $is_artista ? "GERENCIAR AGENDA" : "AGENDAR SESSÃO"; ?></h2>
                <p class="text-white-50"><?php echo $is_artista ? "Aprove ou recuse solicitações e gerencie seu calendário." : "Selecione uma data disponível para ver os horários."; ?></p>
            </div>

            <?php if ($is_artista): ?>

                <ul class="nav nav-tabs nav-tabs-dark mb-0" id="abasAgendaArtista" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="calendario-tab" data-bs-toggle="tab" data-bs-target="#tab-calendario" type="button" role="tab" aria-controls="tab-calendario" aria-selected="true">Calendário</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="solicitacoes-tab" data-bs-toggle="tab" data-bs-target="#tab-solicitacoes" type="button" role="tab" aria-controls="tab-solicitacoes" aria-selected="false">Solicitações Pendentes</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sessoes-tab" data-bs-toggle="tab" data-bs-target="#tab-sessoes" type="button" role="tab" aria-controls="tab-sessoes" aria-selected="false">Sessões Agendadas</button>
                    </li>
                </ul>

                <div class="tab-content tab-content-boxed" id="abasRelatoriosConteudo" style="border-top-left-radius: 0;">

                    <div class="tab-pane fade show active" id="tab-calendario" role="tabpanel" aria-labelledby="calendario-tab">
                        <div class="calendario-container p-0" style="border: none; background: none; margin-bottom: 0;">
                            <div class="calendario-header text-center mb-4 d-flex justify-content-between align-items-center">
                                <?php
                                $href_anterior = "?mes={$mes_anterior}&ano={$ano_anterior}";
                                $classe_anterior = "btn btn-outline-light";
                                if ($ano == $ANO_VISUALIZACAO && $mes == $MES_ATIVO) {
                                    $href_anterior = "#";
                                    $classe_anterior = "btn btn-outline-light disabled";
                                }
                                ?>
                                <a href="<?php echo $href_anterior; ?>" class="<?php echo $classe_anterior; ?>">◄</a>

                                <form method="GET" class="d-flex align-items-center">
                                    <select name="mes" class="form-select select-calendario mx-2" onchange="this.form.submit()">
                                        <?php foreach ($meses_pt as $num => $nome):
                                            if ($ano == $ANO_VISUALIZACAO && $num + 1 < $MES_ATIVO) continue; ?>
                                            <option value="<?php echo $num + 1; ?>" <?php if ($num + 1 == $mes) echo 'selected'; ?>><?php echo $nome; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="ano" class="form-select select-calendario" onchange="this.form.submit()">
                                        <?php for ($a = $ANO_VISUALIZACAO; $a <= $ANO_VISUALIZACAO + 5; $a++): ?>
                                            <option value="<?php echo $a; ?>" <?php if ($a == $ano) echo 'selected'; ?>><?php echo $a; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </form>
                                <a href="?mes=<?php echo $mes_proximo; ?>&ano=<?php echo $ano_proximo; ?>" class="btn btn-outline-light">►</a>
                            </div>

                            <div class="calendario-grid">
                                <div class="dia-semana">Dom</div>
                                <div class="dia-semana">Seg</div>
                                <div class="dia-semana">Ter</div>
                                <div class="dia-semana">Qua</div>
                                <div class="dia-semana">Qui</div>
                                <div class="dia-semana">Sex</div>
                                <div class="dia-semana">Sáb</div>
                                <?php
                                for ($i = 0; $i < $primeiro_dia_semana; $i++) {
                                    echo "<div class='dia outro-mes'></div>";
                                }

                                for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
                                    $data_atual_formatada = date('Y-m-d', mktime(0, 0, 0, $mes, $dia, $ano));
                                    $data_formatada_br = date('d/m/Y', strtotime($data_atual_formatada));
                                    $dia_da_semana_atual = date('w', strtotime($data_atual_formatada));
                                    $onclick_action = "mostrarAgendaDia(event, '{$data_atual_formatada}', '{$data_formatada_br}')";
                                    $extra_class = '';

                                    if (in_array($dia_da_semana_atual, $dias_folga_semana) || in_array($data_atual_formatada, $dias_bloqueados_manualmente)) {
                                        echo "<a href='#' onclick=\"{$onclick_action}\" class='dia dia-bloqueado'>$dia</a>";
                                    } else {
                                        if (in_array($data_atual_formatada, $dias_com_agendamento)) {
                                            if ($data_atual_formatada < $data_hoje_formatada) {
                                                $extra_class = ' dia-concluido';
                                            } else {
                                                $extra_class = ' dia-agendado';
                                            }
                                        } else {
                                            $extra_class = ' dia-livre';
                                        }

                                        if ($data_atual_formatada < $data_hoje_formatada) {
                                            $extra_class .= ' dia-passado';
                                        }

                                        if ($data_atual_formatada == $data_hoje_formatada) {
                                            $extra_class .= ' dia-hoje';
                                        }

                                        echo "<a href='#' onclick=\"{$onclick_action}\" class='dia{$extra_class}'>$dia</a>";
                                    }
                                }

                                $total_celulas = $primeiro_dia_semana + $total_dias_mes;
                                while ($total_celulas % 7 != 0) {
                                    echo "<div class='dia outro-mes'></div>";
                                    $total_celulas++;
                                }
                                ?>
                            </div>
                            <div class="text-end mt-4"><button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalDisponibilidade"><i class="bi bi-calendar-x me-2"></i>Gerenciar Bloqueios</button></div>
                        </div>

                        <div id="secao-detalhes" class="mt-5" style="display: none;"></div>
                    </div>

                    <div class="tab-pane fade" id="tab-solicitacoes" role="tabpanel" aria-labelledby="solicitacoes-tab">
                        <?php if (empty($solicitacoes_pendentes)): ?>
                            <div class="card-resumo text-center text-white-50 mb-0">
                                Nenhuma solicitação pendente no momento.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="acordeaoSolicitacoes">
                                <?php foreach ($solicitacoes_pendentes as $i => $req): ?>
                                    <div class="accordion-item mb-2">
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

                    <div class="tab-pane fade" id="tab-sessoes" role="tabpanel" aria-labelledby="sessoes-tab">
                        <?php if (empty($proximas_sessoes)): ?>
                            <div class="card-resumo text-center text-white-50 mb-0">
                                Nenhuma sessão agendada.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="acordeaoSessoesAgendadas">
                                <?php foreach ($proximas_sessoes as $i => $sessao):
                                    $data_sessao = new DateTime($sessao['data_hora']);
                                ?>
                                    <div class="accordion-item mb-2">
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
                                                    <button class="btn btn-sm btn-outline-danger btn-cancelar-sessao" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            <?php else: ?>
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-7">
                        <div class="formulario-container text-center">
                            <h3 class="text-warning mb-3">Acesso Inválido</h3>
                            <p class="text-white-50 mb-4">Para acessar o calendário e agendar sua sessão, você precisa primeiro selecionar um orçamento aprovado na sua página de agendamentos.</p>
                            <div class="d-grid gap-2">
                                <a href="agendamentos-cliente.php" class="btn btn-primary">VER MEUS AGENDAMENTOS</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <h3 class="text-center text-white">Você não tem permissão.</h3>
        <?php endif; ?>

    </div>
</main>

<?php if ($is_artista): ?>

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
                                <option value="Fechamento (Dia Todo)">Fechamento (dia todo)</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="qtd_sessoes" class="form-label text-light">Estimativa de Sessões Necessárias:</label>
                            <input type="number" class="form-control bg-dark text-light border-secondary" id="qtd_sessoes" name="qtd_sessoes" min="1" max="20" required>
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
                            <textarea class="form-control bg-dark text-light border-secondary" id="motivo_recusa" name="motivo_recusa" rows="3" required></textarea>
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

    <div class="modal fade" id="modalCancelar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light bg-dark">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title">Cancelar Sessão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <form action="../actions/a.cancelar-sessao.php" method="POST">
                        <input type="hidden" name="sessao_id" id="inputSessaoId" value="">
                        <div class="mb-3">
                            <label for="motivo_cancelamento" class="form-label text-light">Motivo do cancelamento:</label>
                            <textarea class="form-control bg-dark text-light border-secondary" name="motivo_cancelamento" rows="3" required></textarea>
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

    <div class="modal fade" id="modalDisponibilidade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light bg-dark">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title">Gerenciar Disponibilidade</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <p>Futuramente você poderá bloquear seus dias por aqui.</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    const isArtista = <?php echo $is_artista ? 'true' : 'false'; ?>;

    // INJEÇÃO DE DADOS DO PHP PARA O JAVASCRIPT
    const sessoesNoBanco = <?php echo json_encode($todas_sessoes_array ?? []); ?>;
    const diasBloqueados = <?php echo json_encode($dias_bloqueados_manualmente); ?>;

    function mostrarAgendaDia(event, dataSql, dataBr) {
        event.preventDefault();
        if (!isArtista) return;

        const secaoDetalhes = document.getElementById('secao-detalhes');
        let agendamentosDoDia = '';

        // Verifica se é dia de folga/bloqueado manual
        if (diasBloqueados.includes(dataSql)) {
            agendamentosDoDia = `<div class="list-group-item text-center text-white-50 bg-dark border-secondary">Dia bloqueado (Folga/Evento).</div>`;
        }
        // Procura no JSON gerado pelo banco de dados se existem sessões nesse dia
        else if (sessoesNoBanco[dataSql] && sessoesNoBanco[dataSql].length > 0) {

            sessoesNoBanco[dataSql].forEach(sessao => {
                let estiloConcluido = sessao.status === 'Concluída' ? 'style="border-left: 4px solid #103e11;"' : '';
                let statusBadge = sessao.status === 'Agendada' ? '<span class="badge bg-primary">Agendada</span>' : '<span class="badge bg-secondary">Concluída</span>';

                let botaoCancelar = sessao.status === 'Agendada' ? `<div class="text-end mt-3"><button class="btn btn-sm btn-outline-danger btn-cancelar-sessao-js" data-id="${sessao.id}" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button></div>` : '';

                agendamentosDoDia += `
                <div class="list-group-item flex-column align-items-start bg-dark border-secondary text-light mb-2 rounded" ${estiloConcluido}>
                    <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                        <h5 class="mb-0 text-info">${sessao.hora}</h5>
                        ${statusBadge}
                    </div>
                    <p class="mb-1"><strong>Cliente:</strong> ${sessao.cliente}</p>
                    <small class="text-white-50"><strong>Projeto:</strong> ${sessao.titulo}</small>
                    ${botaoCancelar}
                </div>`;
            });
        } else {
            agendamentosDoDia = `<div class="list-group-item text-center text-white-50 bg-dark border-secondary">Nenhum agendamento para este dia.</div>`;
        }

        const conteudoHtml = `
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-7">
                    <div class="formulario-container p-4" style="margin-bottom: 0;">
                        <h4 class="text-center mb-4">Agenda de ${dataBr}</h4>
                        <div class="list-group border-0">${agendamentosDoDia}</div>
                    </div>
                </div>
            </div>`;

        secaoDetalhes.innerHTML = conteudoHtml;
        secaoDetalhes.style.display = 'block';
        secaoDetalhes.scrollIntoView({
            behavior: 'smooth'
        });

        // Reconectar o botão gerado pelo JS ao modal de cancelar
        document.querySelectorAll('.btn-cancelar-sessao-js').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('inputSessaoId').value = this.getAttribute('data-id');
            });
        });
    }

    <?php if ($is_artista): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Modais de Aprovar/Recusar/Cancelar listagem estática
            document.querySelectorAll('.btn-aprovar').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('inputAprovarId').value = this.getAttribute('data-id');
                });
            });
            document.querySelectorAll('.btn-recusar').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('inputRecusarId').value = this.getAttribute('data-id');
                });
            });
            document.querySelectorAll('.btn-cancelar-sessao').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('inputSessaoId').value = this.getAttribute('data-id');
                });
            });

            // Lógica de Tabs e Acordeões
            var tabs = document.querySelectorAll('#abasAgendaArtista button[data-bs-toggle="tab"]');
            tabs.forEach(function(tab) {
                tab.addEventListener('show.bs.tab', function(event) {
                    var containerId = event.target.getAttribute('data-bs-target');
                    var containerAtivo = document.querySelector(containerId);
                    var openCollapses = document.querySelectorAll('#abasRelatoriosConteudo .accordion-collapse.show');
                    openCollapses.forEach(function(collapse) {
                        if (!containerAtivo.contains(collapse)) {
                            new bootstrap.Collapse(collapse, {
                                toggle: false
                            }).hide();
                        }
                    });
                });
            });
        });
    <?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>