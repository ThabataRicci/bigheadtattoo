<?php
session_start();
require_once '../includes/conexao.php'; // Conecta ao banco de dados

$is_artista = (isset($_SESSION['loggedin']) && isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'artista');
$id_usuario_logado = $_SESSION['usuario_id'] ?? 0;

$ANO_VISUALIZACAO = 2026;
$ANO_ATIVO = 2026;
$MES_ATIVO = 01;

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
                          WHERE o.status = 'Pendente' OR o.status IS NULL OR o.status = 'Negociacao'
                          ORDER BY o.data_envio ASC";
        $solicitacoes_pendentes = $pdo->query($sql_pendentes)->fetchAll();

        // busca todas as sessões e pega dados do orcamento
        $sql_sessoes = "SELECT s.id_sessao, s.data_hora, s.status, p.titulo, p.id_projeto, p.status AS status_projeto, u.nome AS nome_cliente,
                               o.local_corpo, o.descricao_ideia, o.referencia_ideia, o.valor_sessao, o.estimativa_tempo, o.qtd_sessoes,
                               (SELECT COUNT(*) FROM sessao s2 WHERE s2.id_projeto = p.id_projeto AND s2.status = 'Concluído') AS sessoes_realizadas
                        FROM sessao s 
                        JOIN projeto p ON s.id_projeto = p.id_projeto 
                        JOIN usuario u ON p.id_usuario = u.id_usuario 
                        LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento
                        ORDER BY s.data_hora ASC";
        $todas_sessoes = $pdo->query($sql_sessoes)->fetchAll();

        $dias_concluidos = [];

        foreach ($todas_sessoes as $s) {
            if ($s['status'] === 'Agendado') {
                $proximas_sessoes[] = $s;
            }
            $data_apenas = date('Y-m-d', strtotime($s['data_hora']));

            if ($s['status'] === 'Concluído' && !in_array($data_apenas, $dias_concluidos)) {
                $dias_concluidos[] = $data_apenas;
            } elseif ($s['status'] === 'Agendado' && !in_array($data_apenas, $dias_com_agendamento)) {
                $dias_com_agendamento[] = $data_apenas;
            }

            if ($s['status'] !== 'Cancelado') {
                $todas_sessoes_array[$data_apenas][] = [
                    'id' => $s['id_sessao'],
                    'id_projeto' => $s['id_projeto'],
                    'hora' => date('H:i', strtotime($s['data_hora'])),
                    'titulo' => htmlspecialchars($s['titulo']),
                    'cliente' => htmlspecialchars($s['nome_cliente']),
                    'status' => $s['status'],
                    'local_corpo' => htmlspecialchars($s['local_corpo'] ?? ''),
                    'ideia' => htmlspecialchars($s['descricao_ideia'] ?? ''),
                    'ref' => htmlspecialchars($s['referencia_ideia'] ?? ''),
                    'valor' => htmlspecialchars($s['valor_sessao'] ?? 'Não definido'),
                    'duracao' => htmlspecialchars($s['estimativa_tempo'] ?? 'Não definida'),
                    'sessoes_estimadas' => htmlspecialchars($s['qtd_sessoes'] ?? '-'),
                    'sessoes_realizadas' => $s['sessoes_realizadas'], // NOVO
                    'status_projeto' => $s['status_projeto'] // NOVO
                ];
            }
        }
        // 3. BUSCA DO HISTÓRICO GERAL DO ARTISTA
        $sql_hist = "SELECT p.*, o.local_corpo, o.tamanho_aproximado, o.descricao_ideia, o.qtd_sessoes, o.referencia_ideia, o.valor_sessao, o.estimativa_tempo, u.nome AS nome_cliente 
                     FROM projeto p 
                     LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento
                     JOIN usuario u ON p.id_usuario = u.id_usuario
                     WHERE p.status IN ('Finalizado', 'Cancelado')
                     ORDER BY p.id_projeto DESC";
        $historico_artista = [];

        foreach ($pdo->query($sql_hist)->fetchAll() as $row) {
            $stmt_hist = $pdo->prepare("SELECT data_hora, status, motivo_cancelamento FROM sessao WHERE id_projeto = ? ORDER BY data_hora ASC");
            $stmt_hist->execute([$row['id_projeto']]);

            $historico_montado = [];
            $contador = 1;
            $ultima_data = '1970-01-01 00:00:00';

            foreach ($stmt_hist->fetchAll() as $h) {
                $d = new DateTime($h['data_hora']);
                $ultima_data = $h['data_hora'];
                if ($h['status'] == 'Concluído') {
                    $historico_montado[] = ['desc' => "{$contador}ª Sessão: Concluída em " . $d->format('d/m/Y'), 'icone' => 'bi-check-circle-fill text-success'];
                    $contador++;
                } elseif ($h['status'] == 'Cancelado') {
                    $motivo = htmlspecialchars($h['motivo_cancelamento'] ?? 'Desistência/Imprevisto');
                    $historico_montado[] = ['desc' => "Sessão Cancelada em " . $d->format('d/m/Y') . " | Motivo: {$motivo}", 'icone' => 'bi-x-circle-fill text-danger'];
                }
            }

            $historico_artista[] = [
                'id_projeto' => $row['id_projeto'],
                'titulo' => htmlspecialchars($row['titulo']) . ' | ' . htmlspecialchars($row['nome_cliente']),
                'status' => $row['status'],
                'status_class' => ($row['status'] == 'Finalizado') ? 'status-concluido' : 'status-cancelado',
                'local' => htmlspecialchars($row['local_corpo'] ?? 'Não informado'),
                'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado'] ?? 'Não informado'),
                'ideia' => htmlspecialchars($row['descricao_ideia'] ?? ''),
                'sessoes_estimadas' => htmlspecialchars($row['qtd_sessoes'] ?? '-'),
                'sessoes_realizadas' => $contador - 1, // <-- ADICIONADO AQUI
                'duracao' => htmlspecialchars($row['estimativa_tempo'] ?? 'A definir'),
                'valor' => htmlspecialchars($row['valor_sessao'] ?? 'Não definido'),
                'ref' => htmlspecialchars($row['referencia_ideia'] ?? ''),
                'historico_sessoes' => $historico_montado,
                'data_sort' => $ultima_data
            ];
        }

        // Orçamentos recusados/cancelados
        $sql_rec = "SELECT o.*, u.nome AS nome_cliente FROM orcamento o JOIN usuario u ON o.id_usuario = u.id_usuario WHERE o.status IN ('Recusado', 'Cancelado pelo Cliente')";
        foreach ($pdo->query($sql_rec)->fetchAll() as $row) {
            $motivo_exibicao = !empty($row['motivo_cancelamento_cliente']) ? htmlspecialchars($row['motivo_cancelamento_cliente']) : htmlspecialchars($row['motivo_recusa'] ?? 'Sem detalhes');
            $historico_artista[] = [
                'tipo' => 'recusado',
                'id_projeto' => 0,
                'titulo' => 'Orçamento | ' . htmlspecialchars($row['nome_cliente']),
                'status' => $row['status'],
                'status_class' => 'status-cancelado',
                'local' => htmlspecialchars($row['local_corpo']),
                'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
                'ideia' => htmlspecialchars($row['descricao_ideia']),
                'sessoes_estimadas' => htmlspecialchars($row['qtd_sessoes'] ?? '-'),
                'sessoes_realizadas' => 0, // <-- ADICIONADO AQUI
                'duracao' => htmlspecialchars($row['estimativa_tempo'] ?? 'A definir'),
                'valor' => htmlspecialchars($row['valor_sessao'] ?? 'Não definido'),
                'ref' => htmlspecialchars($row['referencia_ideia'] ?? ''),
                'detalhe_status' => $motivo_exibicao,
                'data_sort' => $row['data_envio'] ?? '1970-01-01 00:00:00'
            ];
        }
        usort($historico_artista, function ($a, $b) {
            return strtotime($b['data_sort']) - strtotime($a['data_sort']);
        });
    } catch (PDOException $e) {
    }
}

// Dias fixos para o calendário
$dias_folga_semana = [0]; // Domingo

// Busca os bloqueios manuais no banco de dados
$dias_bloqueados_manualmente = [];
$bloqueios_banco = [];

try {
    $id_para_busca_bloqueio = 0;

    // Busca o ID do artista globalmente para checar os horários da agenda do estúdio
    $stmt_art = $pdo->query("SELECT id_usuario FROM usuario WHERE perfil = 'artista' LIMIT 1");
    $id_para_busca_bloqueio = $stmt_art->fetchColumn();

    if ($id_para_busca_bloqueio > 0) {
        $sql_bloq = "SELECT id_bloqueio, data_bloqueio FROM bloqueio_agenda WHERE id_artista = ? ORDER BY data_bloqueio ASC";
        $stmt_bloq = $pdo->prepare($sql_bloq);
        $stmt_bloq->execute([$id_para_busca_bloqueio]);
        $bloqueios_banco = $stmt_bloq->fetchAll();

        foreach ($bloqueios_banco as $b) {
            $dias_bloqueados_manualmente[] = $b['data_bloqueio'];
        }
    }
} catch (PDOException $e) {
    // Silencioso
}

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

$projeto_id = isset($_GET['projeto_id']) ? (int)$_GET['projeto_id'] : 0;

$cliente_pode_agendar = true;
if (!$is_artista && $projeto_id === 0) {
    $cliente_pode_agendar = false;
}

$titulo_pagina = $is_artista ? "Gerenciar Agenda" : "Escolha o Dia e Horário";

$hoje_dia = date('d');
$hoje_mes = date('m');
$hoje_ano = date('Y');
$data_hoje_formatada = date('Y-m-d');

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

    /* PINTA A BOLINHA DE VERDE PROS DIAS CONCLUIDOS */
    .dia-concluido::after {
        background-color: #198754 !important;
        /* Verde Bootstrap */
    }
</style>

<main>
    <div class="container my-5 py-5">

        <?php if ($is_artista || $cliente_pode_agendar): ?>

            <?php if (isset($_GET['sucesso'])): ?>
                <?php if ($_GET['sucesso'] == 'concluido'): ?>
                    <div class="alert alert-success text-center mb-4 alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i> Sessão marcada como concluída com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($_GET['sucesso'] == 'sessao_liberada'): ?>
                    <div class="alert alert-info text-center mb-4 alert-dismissible fade show" role="alert">
                        <i class="bi bi-unlock me-2"></i> Nova sessão liberada! O cliente já pode escolher uma nova data.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($_GET['sucesso'] == 'reagendado'): ?>
                    <div class="alert alert-warning text-center mb-4 alert-dismissible fade show" role="alert">
                        <i class="bi bi-calendar-x me-2"></i> O pedido de reagendamento foi enviado ao cliente.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($_GET['sucesso'] == 'bloqueado'): ?>
                    <div class="alert alert-success text-center mb-4 alert-dismissible fade show" role="alert">
                        <i class="bi bi-calendar-minus me-2"></i> Dia bloqueado na sua agenda com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($_GET['sucesso'] == 'desbloqueado'): ?>
                    <div class="alert alert-info text-center mb-4 alert-dismissible fade show" role="alert">
                        <i class="bi bi-calendar-plus me-2"></i> Dia liberado! Agora ele está disponível para agendamentos.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($_GET['sucesso'] == 'proposta_enviada'): ?>
                    <div class="alert alert-success text-center mb-4 alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i> Orçamento aprovado! A proposta foi enviada ao cliente.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($_GET['sucesso'] == 'recusado'): ?>
                    <div class="alert alert-warning text-center mb-4 alert-dismissible fade show" role="alert">
                        <i class="bi bi-x-circle me-2"></i> O orçamento foi recusado e removido das pendências.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['erro']) && $_GET['erro'] == 'bd'): ?>
                <div class="alert alert-danger text-center mb-4 alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i> Ocorreu um erro ao tentar salvar no banco de dados.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button" role="tab" aria-controls="tab-historico" aria-selected="false">Histórico</button>
                    </li>
                </ul>

                <div class="tab-content tab-content-boxed" id="abasRelatoriosConteudo" style="border-top-left-radius: 0;">

                    <div class="tab-pane fade show active" id="tab-calendario" role="tabpanel" aria-labelledby="calendario-tab">
                        <div class="calendario-container p-0" style="border: none; background: none; margin-bottom: 0;">
                            <div class="calendario-header text-center mb-4 d-flex justify-content-between align-items-center">
                                <?php
                                $link_extra = ($projeto_id > 0) ? "&projeto_id={$projeto_id}" : "";
                                $href_anterior = "?mes={$mes_anterior}&ano={$ano_anterior}{$link_extra}";
                                $classe_anterior = "btn btn-outline-light";
                                if ($ano == $ANO_VISUALIZACAO && $mes == $MES_ATIVO) {
                                    $href_anterior = "#";
                                    $classe_anterior = "btn btn-outline-light disabled";
                                }
                                ?>
                                <a href="<?php echo $href_anterior; ?>" class="<?php echo $classe_anterior; ?>">◄</a>

                                <form method="GET" class="d-flex align-items-center">
                                    <?php if ($projeto_id > 0): ?>
                                        <input type="hidden" name="projeto_id" value="<?php echo $projeto_id; ?>">
                                    <?php endif; ?>
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
                                <a href="?mes=<?php echo $mes_proximo; ?>&ano=<?php echo $ano_proximo; ?><?php echo $link_extra; ?>" class="btn btn-outline-light">►</a>
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
                                        // PRIORIDADE 1: Se tem algo pendente, avisa o artista!
                                        if (in_array($data_atual_formatada, $dias_com_agendamento)) {
                                            $extra_class = ' dia-agendado';
                                        }
                                        // PRIORIDADE 2: Só fica listrado se 100% das sessões estiverem concluídas
                                        elseif (in_array($data_atual_formatada, $dias_concluidos)) {
                                            $extra_class = ' dia-concluido';
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

                    <?php if ($is_artista): ?>
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
                                                            <span class="me-3 text-light"><i class="bi bi-calendar3 me-1"></i> <?php echo $data_sessao->format('d/m/Y - H:i'); ?></span>
                                                        </div>
                                                        <span class="mt-1 small text-white-50"><strong>Cliente:</strong> <?php echo htmlspecialchars($sessao['nome_cliente']); ?></span>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="sessao-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                                                <div class="accordion-body text-white-50">

                                                    <div class="small mb-3">
                                                        <p class="mb-1"><strong>Local:</strong> <?php echo htmlspecialchars($sessao['local_corpo'] ?? 'Não informado'); ?></p>
                                                        <p class="mb-1"><strong>Ideia:</strong> "<?php echo htmlspecialchars($sessao['descricao_ideia'] ?? 'Não informada'); ?>"</p>
                                                        <p class="mb-1"><strong>Duração:</strong> <?php echo htmlspecialchars($sessao['estimativa_tempo'] ?? 'A definir'); ?></p>
                                                        <p class="mb-1"><strong>Sessões Estimadas:</strong> <?php echo htmlspecialchars($sessao['qtd_sessoes'] ?? '-'); ?></p>
                                                        <p class="mb-1"><strong>Valor:</strong> R$ <?php echo htmlspecialchars($sessao['valor_sessao'] ?? 'Não definido'); ?></p>
                                                        <p class="mb-1"><strong>Referência:</strong>
                                                            <?php if (!empty($sessao['referencia_ideia'])): ?>
                                                                <a href="../imagens/orcamentos/<?php echo htmlspecialchars($sessao['referencia_ideia']); ?>" target="_blank" class="text-info text-decoration-none">
                                                                    <i class="bi bi-image me-1"></i> Ver Anexo
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-white-50">Vazio</span>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>

                                                    <div class="d-flex justify-content-end mt-3 gap-2 border-top border-secondary pt-3 flex-wrap">
                                                        <button class="btn btn-sm btn-success btn-concluir-sessao-js" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalConfirmarConcluir">
                                                            <i class="bi bi-check-lg me-1"></i>Concluído
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-info btn-liberar-sessao-js" data-idproj="<?php echo $sessao['id_projeto']; ?>" data-bs-toggle="modal" data-bs-target="#modalConfirmarLiberar">
                                                            <i class="bi bi-unlock me-1"></i>Liberar Nova Sessão
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalReagendarArtista">Reagendar</button>
                                                        <button class="btn btn-sm btn-outline-danger btn-cancelar-sessao" data-id="<?php echo $sessao['id_sessao']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Projeto</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane fade" id="tab-historico" role="tabpanel" aria-labelledby="historico-tab">
                            <div class="p-3">
                                <h4 class="mb-4">Histórico Geral</h4>
                                <?php if (empty($historico_artista)): ?>
                                    <div class="card-resumo text-center text-white-50 mb-0">O histórico está vazio.</div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-end mb-3">
                                        <select id="filtroStatusHistoricoArt" class="form-select form-select-sm bg-dark text-light border-secondary w-auto shadow-none">
                                            <option value="todos">Ver Tudo</option>
                                            <option value="Finalizado">Finalizados</option>
                                            <option value="Cancelado">Cancelados</option>
                                            <option value="Recusado">Recusados</option>
                                        </select>
                                    </div>
                                    <div class="accordion" id="acordeaoHistoricoArt">
                                        <?php foreach ($historico_artista as $i => $item): ?>
                                            <div class="accordion-item mb-2 historico-item-art-js" data-status="<?php echo strpos($item['status'], 'Cancelado') !== false ? 'Cancelado' : $item['status']; ?>">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed text-light" type="button" data-bs-toggle="collapse" data-bs-target="#hist-art-<?php echo $i; ?>">
                                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                                            <span><strong><?php echo $item['titulo']; ?></strong></span>
                                                            <span class="badge <?php echo $item['status_class']; ?> me-3"><?php echo $item['status']; ?></span>
                                                        </div>
                                                    </button>
                                                </h2>
                                                <div id="hist-art-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoHistoricoArt">
                                                    <div class="accordion-body text-white-50">

                                                        <div class="small mb-3">
                                                            <p class="mb-1"><strong>Local:</strong> <?php echo $item['local'] . ' - ' . $item['tamanho_desc']; ?></p>
                                                            <p class="mb-1"><strong>Ideia:</strong> "<?php echo $item['ideia']; ?>"</p>
                                                            <p class="mb-1"><strong>Duração:</strong> <?php echo $item['duracao']; ?></p>
                                                            <p class="mb-1"><strong>Sessões Realizadas:</strong> <?php echo $item['sessoes_realizadas'] ?? 0; ?> | Estimado: <?php echo $item['sessoes_estimadas']; ?></p>
                                                            <p class="mb-1"><strong>Valor da Sessão:</strong> R$ <?php echo $item['valor']; ?></p>
                                                            <p class="mb-1"><strong>Referência:</strong>
                                                                <?php if (!empty($item['ref'])): ?>
                                                                    <a href="../imagens/orcamentos/<?php echo $item['ref']; ?>" target="_blank" class="text-info text-decoration-none">
                                                                        <i class="bi bi-image me-1"></i> Ver Anexo
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="text-white-50">Vazio</span>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>

                                                        <hr class="my-3 border-secondary">

                                                        <?php if (isset($item['tipo']) && $item['tipo'] == 'recusado'): ?>
                                                            <p class="text-warning mb-0 small"><i class="bi bi-x-circle-fill text-danger me-2"></i> <strong>Motivo da Recusa/Encerramento:</strong> <?php echo $item['detalhe_status']; ?></p>
                                                        <?php else: ?>
                                                            <p class="mb-2"><strong>Linha do Tempo:</strong></p>

                                                            <div class="p-2 mb-3" style="background-color: #2c2c2c; border-radius: 8px;">
                                                                <?php if (empty($item['historico_sessoes'])): ?>
                                                                    <p class='small mb-0 p-2'>Nenhuma sessão.</p>
                                                                <?php else: ?>
                                                                    <?php foreach ($item['historico_sessoes'] as $hist): ?>
                                                                        <div class="small p-2 border-bottom border-dark">
                                                                            <span class="<?php echo strpos($hist['icone'], 'text-danger') !== false ? 'text-warning' : 'text-white-50'; ?>">
                                                                                <i class="bi <?php echo $hist['icone']; ?> me-2"></i> <?php echo $hist['desc']; ?>
                                                                            </span>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>

                                                            <?php if ($item['status'] === 'Finalizado'): ?>
                                                                <div class="d-flex justify-content-end mt-3 gap-2 border-top border-secondary pt-3">
                                                                    <button type="button" class="btn btn-sm btn-outline-info btn-liberar-sessao-js" data-idproj="<?php echo $item['id_projeto']; ?>" data-bs-toggle="modal" data-bs-target="#modalConfirmarLiberar">
                                                                        <i class="bi bi-unlock me-1"></i>Liberar Nova Sessão
                                                                    </button>
                                                                </div>
                                                            <?php endif; ?>

                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php endif; ?>
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
                    <h5 class="modal-title">Enviar Proposta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <form action="../actions/a.aprovar-orcamento.php" method="POST">
                        <input type="hidden" name="orcamento_id" id="inputAprovarId" value="">
                        <input type="hidden" name="origem" value="<?php echo basename($_SERVER['PHP_SELF']); ?>">

                        <div class="mb-3">
                            <label for="titulo_projeto" class="form-label text-light">Título do Projeto:</label>
                            <input type="text" class="form-control bg-dark text-light border-secondary" id="titulo_projeto" name="titulo_projeto" placeholder="Ex: Fechamento Samurai" required>
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
                            <button type="submit" class="btn btn-success">Enviar Proposta</button>
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
                    <h5 class="modal-title text-danger">Cancelar Projeto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <p>Atenção: O projeto inteiro será cancelado e não poderá ser reagendado.</p>
                    <form action="../actions/a.cancelar-projeto.php" method="POST">
                        <input type="hidden" name="sessao_id" id="inputSessaoId" value="">
                        <div class="mb-3">
                            <label for="motivo_cancelamento" class="form-label text-light">Motivo:</label>
                            <textarea class="form-control bg-dark text-light border-secondary" name="motivo" rows="3" required></textarea>
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

    <div class="modal fade" id="modalConfirmarConcluir" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light bg-dark">
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
            <div class="modal-content text-light bg-dark">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title text-info">Liberar Nova Sessão</h5> <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <p>Deseja marcar a sessão atual como concluída e pedir para o cliente agendar a próxima?</p>
                    <form action="../actions/a.liberar-sessao.php" method="POST"> <input type="hidden" name="projeto_id" id="inputConfirmarLiberarId" value="">
                        <div class="modal-footer border-top border-secondary p-0 pt-3"> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button> <button type="submit" class="btn btn-info text-white">Liberar</button> </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDisponibilidade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light bg-dark">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title">Gerenciar Bloqueios</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <form action="../actions/a.bloquear-dia.php" method="POST" class="mb-4">
                        <label for="data_bloqueio" class="form-label text-light">Bloquear Dia:</label>
                        <div class="input-group">
                            <input type="date" class="form-control bg-dark text-light border-secondary" id="data_bloqueio" name="data_bloqueio" required>
                            <button class="btn btn-warning text-dark fw-bold" type="submit">Bloquear</button>
                        </div>
                        <div class="form-text text-white-50 mt-2"><i class="bi bi-info-circle me-1"></i> Domingos já são bloqueados automaticamente.</div>
                    </form>

                    <hr class="border-secondary my-4">
                    <h6 class="text-light mb-3">Dias Bloqueados:</h6>

                    <?php if (empty($bloqueios_banco)): ?>
                        <div class="alert alert-dark text-center border-secondary small mb-0">Nenhum dia bloqueado manualmente.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush border border-secondary rounded">
                            <?php foreach ($bloqueios_banco as $b): ?>
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between align-items-center py-2 px-3">
                                    <span><i class="bi bi-calendar-x text-warning me-2"></i> <?php echo date('d/m/Y', strtotime($b['data_bloqueio'])); ?></span>
                                    <form action="../actions/a.desbloquear-dia.php" method="POST" class="m-0 p-0">
                                        <input type="hidden" name="id_bloqueio" value="<?php echo $b['id_bloqueio']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Desbloquear e abrir na agenda"><i class="bi bi-trash"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
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

    // VARIÁVEIS EXCLUSIVAS DO CLIENTE
    const horariosOcupados = <?php echo json_encode($horarios_ocupados ?? []); ?>;
    const estimativaTempo = "<?php echo $estimativa_tempo_projeto ?? 'Projeto Pequeno (Até 2h)'; ?>";
    const projetoIdCliente = <?php echo $projeto_id ?? 0; ?>;

    function mostrarAgendaDia(event, dataSql, dataBr) {
        event.preventDefault();
        const secaoDetalhes = document.getElementById('secao-detalhes');

        // --- LÓGICA EXCLUSIVA PARA O ARTISTA ---
        let agendamentosDoDia = '';

        // Verifica se é dia de folga/bloqueado manual
        if (diasBloqueados.includes(dataSql)) {
            agendamentosDoDia = `<div class="list-group-item text-center text-white-50 bg-dark border-secondary">Dia bloqueado.</div>`;
        }
        // Procura no JSON gerado pelo banco de dados se existem sessões nesse dia
        else if (sessoesNoBanco[dataSql] && sessoesNoBanco[dataSql].length > 0) {

            sessoesNoBanco[dataSql].forEach(sessao => {
                let statusBadge = sessao.status === 'Concluído' ? '<span class="badge bg-success">Concluído</span>' : '<span class="badge bg-primary">Agendado</span>';

                let local = sessao.local_corpo ? sessao.local_corpo : 'Não informado';
                let ideia = sessao.ideia ? sessao.ideia : 'Não informada';
                let refLink = sessao.ref ? `<a href="../imagens/orcamentos/${sessao.ref}" target="_blank" class="text-info text-decoration-none"><i class="bi bi-image me-1"></i>Anexo</a>` : 'Vazio';
                let valor = sessao.valor;
                let duracao = sessao.duracao; // Nova variável
                let sessoesEstimadas = sessao.sessoes_estimadas; // Nova variável

                let botoesAcao = '';
                if (sessao.status === 'Agendado') {
                    botoesAcao = `
                    <div class="d-flex justify-content-end mt-3 gap-2 border-top border-secondary pt-3 flex-wrap">
                        <button class="btn btn-sm btn-success btn-concluir-sessao-js" data-id="${sessao.id}" data-bs-toggle="modal" data-bs-target="#modalConfirmarConcluir">
                            <i class="bi bi-check-lg me-1"></i>Concluído
                        </button>
                        <button class="btn btn-sm btn-outline-info btn-liberar-sessao-js" data-idproj="${sessao.id_projeto}" data-bs-toggle="modal" data-bs-target="#modalConfirmarLiberar">
                            <i class="bi bi-unlock me-1"></i>Liberar Nova Sessão
                        </button>
                        <button class="btn btn-sm btn-outline-warning btn-reagendar-sessao-js" data-id="${sessao.id}" data-bs-toggle="modal" data-bs-target="#modalReagendarArtista">
                            Reagendar
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-cancelar-sessao-js" data-id="${sessao.id}" data-bs-toggle="modal" data-bs-target="#modalCancelar">
                            Cancelar Projeto
                        </button>
                    </div>`;
                } else if (sessao.status === 'Concluído') {
                    botoesAcao = `
                    <div class="d-flex justify-content-end mt-3 gap-2 border-top border-secondary pt-3">
                        <button class="btn btn-sm btn-outline-info btn-liberar-sessao-js" data-idproj="${sessao.id_projeto}" data-bs-toggle="modal" data-bs-target="#modalConfirmarLiberar">
                            <i class="bi bi-unlock me-1"></i>Liberar Nova Sessão
                        </button>
                    </div>`;
                }

                agendamentosDoDia += `
                <div class="list-group-item flex-column align-items-start text-light mb-3 rounded border border-secondary p-3" style="background-color: #2c2c2c;">
                    <div class="d-flex w-100 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 text-info"><i class="bi bi-clock me-1"></i> ${sessao.hora}</h5>
                        ${statusBadge}
                    </div>
                    <p class="mb-1 text-light"><strong>${sessao.titulo}</strong></p>
                    <p class="mb-3 text-white-50 small"><i class="bi bi-person me-1"></i> Cliente: ${sessao.cliente}</p>
                    
                    <div class="small text-white-50">  
                        <p class="mb-1"><strong>Local:</strong> ${local}</p>
                        <p class="mb-1"><strong>Ideia:</strong> "${ideia}"</p>
                        <p class="mb-0"><strong>Referência:</strong> ${refLink}</p>
                        <p class="mb-1"><strong>Duração:</strong> ${duracao}</p>
                        <p class="mb-1"><strong>Sessões Realizadas:</strong> ${sessao.sessoes_realizadas} | Estimado: ${sessoesEstimadas}</p>
                        <p class="mb-1"><strong>Valor da Sessão:</strong> R$ ${valor}</p>
                    </div>
                    
                    ${botoesAcao}
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

        // Reconectar os botões gerados pelo JS (Para abrir modais)
        document.querySelectorAll('.btn-cancelar-sessao-js').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('inputSessaoId').value = this.getAttribute('data-id');
            });
        });
        document.querySelectorAll('.btn-reagendar-sessao-js').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('inputReagendarIdArtista').value = this.getAttribute('data-id');
            });
        });
        document.querySelectorAll('.btn-concluir-sessao-js').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('inputConfirmarConcluirId').value = this.getAttribute('data-id');
            });
        });
        document.querySelectorAll('.btn-liberar-sessao-js').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('inputConfirmarLiberarId').value = this.getAttribute('data-idproj');
            });
        });
    }
    <?php if ($is_artista): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara inteligente para o Valor da Sessão (Dinheiro)
            const inputsDinheiro = document.querySelectorAll('.mascara-dinheiro');
            inputsDinheiro.forEach(input => {
                input.addEventListener('input', function(e) {
                    let valor = e.target.value;
                    valor = valor.replace(/\D/g, "");
                    if (valor === "") {
                        e.target.value = "";
                        return;
                    }
                    valor = (parseInt(valor, 10) / 100).toFixed(2);
                    valor = valor.replace(".", ",");
                    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
                    e.target.value = valor;
                });
            });

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
            document.querySelectorAll('.btn-concluir-sessao-js').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('inputConfirmarConcluirId').value = this.getAttribute('data-id');
                });
            });
            document.querySelectorAll('.btn-liberar-sessao-js').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('inputConfirmarLiberarId').value = this.getAttribute('data-idproj');
                });
            });
            // --- LÓGICA DO FILTRO DO HISTÓRICO DO ARTISTA ---
            const filtroHistoricoArt = document.getElementById('filtroStatusHistoricoArt');
            if (filtroHistoricoArt) {
                filtroHistoricoArt.addEventListener('change', function() {
                    const filtro = this.value;
                    const itensHistorico = document.querySelectorAll('.historico-item-art-js');
                    itensHistorico.forEach(item => {
                        if (filtro === 'todos' || item.getAttribute('data-status').includes(filtro)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }

            // --- LÓGICA PARA ABRIR ABA DIRETO PELA URL ---
            const urlParams = new URLSearchParams(window.location.search);
            const abaAtiva = urlParams.get('aba');
            if (abaAtiva) {
                const abaBtn = document.getElementById(abaAtiva + '-tab');
                if (abaBtn) {
                    const bsTab = new bootstrap.Tab(abaBtn);
                    bsTab.show();
                }
            }
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