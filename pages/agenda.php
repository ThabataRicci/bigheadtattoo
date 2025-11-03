<?php
session_start();

$is_artista = (isset($_SESSION['loggedin']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'artista');

$ANO_VISUALIZACAO = 2025;
$ANO_ATIVO = 2025;
$MES_ATIVO = 10; // Outubro

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

// Forçar visualização a partir de Outubro de 2025 (mês 10)
if ($ano < $ANO_VISUALIZACAO) {
    $ano = $ANO_VISUALIZACAO;
    $mes = $MES_ATIVO;
}
if ($ano == $ANO_VISUALIZACAO && $mes < $MES_ATIVO) {
    $mes = $MES_ATIVO;
}


$dias_folga_semana = [0]; // domingo

// --- LÓGICA DE DIAS CORRIGIDA ---
// 1. Dias com agendamentos (Clicáveis para o artista, cinza)
$dias_com_agendamento = [
    '2025-10-01', // Thábata (Sessão 1 Concluída)
    '2025-11-01', // Thábata (Borboleta Concluída)
    '2025-11-08', // Thábata (Sessão 2 Ativa)
    '2025-11-15', // Izabella (HP Ativa)
];
// 2. Dias bloqueados manualmente (NÃO-clicáveis para o artista, cinza)
$dias_bloqueados_manualmente = [
    '2025-11-20', // Bloqueio Manual
    '2025-11-21'  // Bloqueio Manual
];
// 3. Array para o CLIENTE (combina todos)
$dias_ocupados_total_cliente = array_merge($dias_com_agendamento, $dias_bloqueados_manualmente);
// --- FIM DA LÓGICA ---


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
// Trava navegação para antes de Outubro 2025
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
include '../includes/header.php';
?>

<?php
// submenu
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
            <a href="solicitar-orcamento.php" class=<<?php echo ($pagina_ativa == 'solicitar.orcamento.php') ? 'active' : ''; ?> ">Orçamento </a>
                <a href=" configuracoes-cliente.php" class="<?php echo ($pagina_ativa == 'configuracoes-cliente.php') ? 'active' : ''; ?>">Configurações</a>
        </div>
    <?php endif; ?>

<?php endif; ?>
<?php
?>


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


                <div class="tab-content" id="abasRelatoriosConteudo">


                    <div class="tab-pane fade show active" id="tab-calendario" role="tabpanel" aria-labelledby="calendario-tab">


                        <div class="calendario-container p-0" style="border: none; background: none; margin-bottom: 0;">
                            <div class="calendario-header text-center mb-4 d-flex justify-content-between align-items-center">
                                <?php // Botão Anterior (com trava)
                                $href_anterior = "?mes={$mes_anterior}&ano={$ano_anterior}";
                                $classe_anterior = "btn btn-outline-light";
                                if ($ano == $ANO_VISUALIZACAO && $mes == $MES_ATIVO) {
                                    $href_anterior = "#";
                                    $classe_anterior = "btn btn-outline-light disabled";
                                }
                                ?>
                                <a href="<?php echo $href_anterior; ?>" class="<?php echo $classe_anterior; ?>">◄</a>

                                <form method="GET" class="d-flex align-items-center">
                                    <select name="mes" class="form-select select-calendario mx-2" onchange="this.form.submit()"><?php
                                                                                                                                foreach ($meses_pt as $num => $nome):
                                                                                                                                    // Trava meses anteriores
                                                                                                                                    if ($ano == $ANO_VISUALIZACAO && $num + 1 < $MES_ATIVO) continue;
                                                                                                                                ?><option value="<?php echo $num + 1; ?>" <?php if ($num + 1 == $mes) echo 'selected'; ?>><?php echo $nome; ?></option><?php endforeach; ?></select>
                                    <select name="ano" class="form-select select-calendario" onchange="this.form.submit()"><?php for ($a = $ANO_VISUALIZACAO; $a <= $ANO_VISUALIZACAO + 5; $a++): ?><option value="<?php echo $a; ?>" <?php if ($a == $ano) echo 'selected'; ?>><?php echo $a; ?></option><?php endfor; ?></select>
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
                                    echo '<div class="dia outro-mes"></div>';
                                }

                                // Simula data "hoje" como 1 de Outubro de 2025 para testes
                                $hoje_dia = 1;
                                $hoje_mes = 10;
                                $hoje_ano = 2025;

                                for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
                                    $data_atual_formatada = date('Y-m-d', mktime(0, 0, 0, $mes, $dia, $ano));
                                    $data_formatada_br = date('d/m/Y', strtotime($data_atual_formatada));
                                    $dia_da_semana_atual = date('w', strtotime($data_atual_formatada));

                                    // 1. Verifica se é NÃO-CLICÁVEL (Passado, Folga, Bloqueio Manual)
                                    if (
                                        ($ano < $hoje_ano || ($ano == $hoje_ano && $mes < $hoje_mes) || ($ano == $hoje_ano && $mes == $hoje_mes && $dia < $hoje_dia)) || // Passado
                                        in_array($dia_da_semana_atual, $dias_folga_semana) || // Folga (Domingo)
                                        in_array($data_atual_formatada, $dias_bloqueados_manualmente) // Bloqueio Manual (20, 21/11)
                                    ) {
                                        echo "<div class='dia dia-ocupado'>$dia</div>"; // DIV (não clicável)

                                        // 2. Verifica se é CLICÁVEL-OCUPADO (Tem agendamento)
                                    } else if (in_array($data_atual_formatada, $dias_com_agendamento)) {
                                        $onclick_action = "mostrarAgendaDia(event, '{$data_atual_formatada}', '{$data_formatada_br}')";
                                        echo "<a href='#' onclick=\"{$onclick_action}\" class='dia dia-ocupado'>$dia</a>"; // A (clicável, ocupado)

                                        // 3. É CLICÁVEL-LIVRE
                                    } else {
                                        $onclick_action = "mostrarAgendaDia(event, '{$data_atual_formatada}', '{$data_formatada_br}')";
                                        echo "<a href='#' onclick=\"{$onclick_action}\" class='dia dia-livre'>$dia</a>"; // A (clicável, livre)
                                    }
                                }

                                $total_celulas = $primeiro_dia_semana + $total_dias_mes;
                                while ($total_celulas % 7 != 0) {
                                    echo '<div class="dia outro-mes"></div>';
                                    $total_celulas++;
                                }
                                ?>
                            </div>
                            <div class="text-end mt-4"><button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalDisponibilidade"><i class="bi bi-calendar-x me-2"></i>Gerenciar Bloqueios</button></div>
                        </div>


                        <div id="secao-detalhes" class="mt-5" style="display: none;"></div>

                    </div>


                    <div class="tab-pane fade" id="tab-solicitacoes" role="tabpanel" aria-labelledby="solicitacoes-tab">
                        <?php // SIMULAÇÃO: 1 solicitação pendente (Thábata)
                        ?>
                        <?php $solicitacoes_pendentes = true; ?>
                        <?php if (!$solicitacoes_pendentes): ?>
                            <div class="card-resumo text-center text-white-50 mb-0">
                                Nenhuma solicitação pendente.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="acordeaoSolicitacoes">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item1">
                                            <div class="w-100 d-flex justify-content-between">
                                                <span><strong>Cliente:</strong> Thábata Ricci | <strong>Ideia:</strong> Rosa Fineline</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="item1" class="accordion-collapse collapse" data-bs-parent="#acordeaoSolicitacoes">
                                        <div class="accordion-body">
                                            <p><strong>Local do Corpo:</strong> Pulso</p>
                                            <p><strong>Tamanho Aproximado:</strong> Pequeno (aprox. 5cm)</p>
                                            <p><strong>Ideia do Cliente:</strong> "Uma rosa pequena e delicada em fineline."</p>
                                            <p><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">rosafineline.jpg

                                                </a></p>
                                            <div class="d-flex justify-content-end align-items-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalRecusar">Recusar</button>
                                                <div class="dropdown ms-2">
                                                    <button class="btn btn-sm btn-success dropdown-toggle" type="button" id="dropdownAprovar" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Aprovar
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownAprovar">
                                                        <li><a class="dropdown-item" href="#">Projeto Pequeno (30 minutos)</a></li>
                                                        <li><a class="dropdown-item" href="#">Projeto Médio (2 horas)</a></li>
                                                        <li><a class="dropdown-item" href="#">Projeto Grande (dia todo)</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>


                    <div class="tab-pane fade" id="tab-sessoes" role="tabpanel" aria-labelledby="sessoes-tab">
                        <?php // SIMULAÇÃO: 2 sessões ativas (Thábata 08/11 e Izabella 15/11)
                        ?>
                        <?php $proximas_sessoes = true; ?>
                        <?php if (!$proximas_sessoes): ?>
                            <div class="card-resumo text-center text-white-50 mb-0">
                                Nenhuma sessão agendada.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="acordeaoSessoesAgendadas">

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessaoPP">
                                            <div class="w-100 d-flex justify-content-between align-items-center">
                                                <span><strong>Projeto:</strong> Fechamento de Braço | <strong>Cliente:</strong> Thábata Ricci</span>
                                                <span class="me-3"><strong>Data:</strong> 08/11/2025 às 10:00</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="sessaoPP" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                                        <div class="accordion-body">
                                            <p class="text-white-50 mb-2"><strong>Detalhes:</strong></p>
                                            <ul class="list-unstyled card-resumo p-3 small">
                                                <li><strong>Local do Corpo:</strong> Perna</li>
                                                <li><strong>Tamanho Aproximado:</strong> Fechamento</li>
                                                <li><strong>Ideia do Cliente:</strong> "Projeto pra fechar o braço."</li>
                                                <li><strong>Referência Enviada:</strong> Nenhuma</li>
                                                <li><strong>Duração da Sessão:</strong> Dia Todo</li>
                                            </ul>
                                            <p class="text-white-50 mb-2 mt-4"><strong>Histórico de Sessões:</strong></p>
                                            <div class="card-resumo p-3">
                                                <div class="d-flex justify-content-between align-items-center small p-2">
                                                    <span><strong>Sessão 1:</strong> Concluída em 01/10/2025</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center small p-2">
                                                    <span><strong>Sessão 2:</strong> Agendada para 08/11/2025 às 10:00</span>
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                                                </div>
                                            </div>
                                            <div class="text-end mt-3 d-flex justify-content-end gap-2">
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalLiberarSessao">Liberar Nova Sessão</button>
                                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalConcluirProjeto">Concluir Projeto</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessaoPG">
                                            <div class="w-100 d-flex justify-content-between align-items-center">
                                                <span><strong>Projeto:</strong> Tatuagem Harry Potter | <strong>Cliente:</strong> Izabella Bianca</span>
                                                <span class="me-3"><strong>Data:</strong> 15/11/2025 às 15:00</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="sessaoPG" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                                        <div class="accordion-body">
                                            <p class="text-white-50 mb-2"><strong>Detalhes:</strong></p>
                                            <ul class="list-unstyled card-resumo p-3 small">
                                                <li><strong>Local do Corpo:</strong> Perna</li>
                                                <li><strong>Tamanho Aproximado:</strong> Médio (11cm a 20cm)</li>
                                                <li><strong>Ideia do Cliente:</strong> "Símbolo Harry Potter na perna."</li>
                                                <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">harrypotter-referencia.jpg</a></li>
                                                <li><strong>Duração da Sessão:</strong> 2 Horas</li>
                                            </ul>
                                            <div class="text-end mt-3">
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>


            <?php else: ?>

                <div class="calendario-container">
                    <div class="calendario-header text-center mb-4 d-flex justify-content-between align-items-center">
                        <?php // Botão Anterior (com trava)
                        $href_anterior = "?mes={$mes_anterior}&ano={$ano_anterior}&projeto_id={$projeto_id}&tamanho={$tamanho}";
                        $classe_anterior = "btn btn-outline-light";
                        if ($ano == $ANO_VISUALIZACAO && $mes == $MES_ATIVO) {
                            $href_anterior = "#";
                            $classe_anterior = "btn btn-outline-light disabled";
                        }
                        ?>
                        <a href="<?php echo $href_anterior; ?>" class="<?php echo $classe_anterior; ?>">◄</a>

                        <form method="GET" class="d-flex align-items-center">
                            <select name="mes" class="form-select select-calendario mx-2" onchange="this.form.submit()"><?php
                                                                                                                        foreach ($meses_pt as $num => $nome):
                                                                                                                            // Trava meses anteriores
                                                                                                                            if ($ano == $ANO_VISUALIZACAO && $num + 1 < $MES_ATIVO) continue;
                                                                                                                        ?><option value="<?php echo $num + 1; ?>" <?php if ($num + 1 == $mes) echo 'selected'; ?>><?php echo $nome; ?></option><?php endforeach; ?></select>
                            <select name="ano" class="form-select select-calendario" onchange="this.form.submit()"><?php for ($a = $ANO_VISUALIZACAO; $a <= $ANO_VISUALIZACAO + 5; $a++): ?><option value="<?php echo $a; ?>" <?php if ($a == $ano) echo 'selected'; ?>><?php echo $a; ?></option><?php endfor; ?></select>
                            <input type="hidden" name="projeto_id" value="<?php echo $projeto_id; ?>"><input type="hidden" name="tamanho" value="<?php echo $tamanho; ?>">
                        </form>
                        <a href="?mes=<?php echo $mes_proximo; ?>&ano=<?php echo $ano_proximo; ?>&projeto_id=<?php echo $projeto_id; ?>&tamanho=<?php echo $tamanho; ?>" class="btn btn-outline-light">►</a>
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
                        // Simula data "hoje" como 1 de Outubro de 2025 para testes
                        $hoje_dia = 1;
                        $hoje_mes = 10;
                        $hoje_ano = 2025;

                        for ($i = 0; $i < $primeiro_dia_semana; $i++) {
                            echo '<div class="dia outro-mes"></div>';
                        }
                        for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
                            $data_atual_formatada = date('Y-m-d', mktime(0, 0, 0, $mes, $dia, $ano));
                            $data_formatada_br = date('d/m/Y', strtotime($data_atual_formatada));
                            $dia_da_semana_atual = date('w', strtotime($data_atual_formatada));

                            // Lógica simples do cliente: ou está ocupado (div) ou está livre (a)
                            if (
                                ($ano < $hoje_ano || ($ano == $hoje_ano && $mes < $hoje_mes) || ($ano == $hoje_ano && $mes == $hoje_mes && $dia < $hoje_dia)) || // Passado
                                in_array($dia_da_semana_atual, $dias_folga_semana) || // Folga
                                in_array($data_atual_formatada, $dias_ocupados_total_cliente) // Ocupado (combina agendamentos + bloqueios)
                            ) {
                                echo "<div class='dia dia-ocupado'>$dia <br><small>Indisponível</small></div>";
                            } else {
                                $onclick_action = "mostrarHorarios(event, '{$data_atual_formatada}', '{$data_formatada_br}')";
                                echo "<a href='#' onclick=\"{$onclick_action}\" class='dia dia-livre'>$dia</a>";
                            }
                        }
                        $total_celulas = $primeiro_dia_semana + $total_dias_mes;
                        while ($total_celulas % 7 != 0) {
                            echo '<div class="dia outro-mes"></div>';
                            $total_celulas++;
                        }
                        ?>
                    </div>
                </div>

                <div id="secao-detalhes" class="mt-5" style="display: none;"></div>

            <?php endif; ?>

        <?php else: ?>

            <?php
            ?>
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
        <?php
        ?>

    </div>
</main>

<?php if ($is_artista): ?>
    <div class="modal fade" id="modalDisponibilidade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gerenciar Disponibilidade</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3"><label class="form-label">Dias de Folga Fixos:</label>
                            <div id="dias-folga-container" class="p-3 formulario-container d-flex">
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="folga_domingo" checked><label class="form-check-label" for="folga_domingo">Dom</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="folga_segunda"><label class="form-check-label" for="folga_segunda">Seg</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="folga_terca"><label class="form-check-label" for="folga_terca">Ter</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="folga_quarta"><label class="form-check-label" for="folga_quarta">Qua</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="folga_quinta"><label class="form-check-label" for="folga_quinta">Qui</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="folga_sexta"><label class="form-check-label" for="folga_sexta">Sex</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" id="folga_sabado"><label class="form-check-label" for="folga_sabado">Sáb</label></div>
                            </div>
                        </div>
                        <div class="mb-3"><label for="dias-bloqueados" class="form-label">Bloquear Datas Específicas:</label>
                            <div class="input-group"><input type="date" class="form-control" id="dias-bloqueados"><button type="button" class="btn btn-outline-light">Adicionar</button></div>
                        </div>
                        <div>
                            <p class="small text-white-50 mb-1">Datas já bloqueadas (sessões ou folgas):</p>
                            <ul class="list-group">
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">01/10/2025 (Sessão Thábata)<button class="btn btn-sm btn-danger py-0 px-2">&times;</button></li>
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">01/11/2025 (Sessão Thábata)<button class="btn btn-sm btn-danger py-0 px-2">&times;</button></li>
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">08/11/2025 (Sessão Thábata)<button class="btn btn-sm btn-danger py-0 px-2">&times;</button></li>
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">15/11/2025 (Sessão Izabella)<button class="btn btn-sm btn-danger py-0 px-2">&times;</button></li>
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">20/11/2025 (Bloqueio Manual)<button class="btn btn-sm btn-danger py-0 px-2">&times;</button></li>
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">21/11/2025 (Bloqueio Manual)<button class="btn btn-sm btn-danger py-0 px-2">&times;</button></li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button><button type="button" class="btn btn-primary">Salvar Alterações</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalRecusar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Recusar Projeto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form onsubmit="alert('Projeto recusado.'); return false;">
                        <div class="mb-3">
                            <label for="motivo_recusa" class="form-label">Motivo:</label>
                            <textarea class="form-control" id="motivo_recusa" name="motivo_recusa" rows="3" required></textarea>
                        </div>
                        <input type="hidden" name="solicitacao_id" value="101">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                            <button type="submit" class="btn btn-danger" data-bs-dismiss="modal">Recusar Projeto</button>
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
                    <form onsubmit="alert('Sessão cancelada.'); return false;">
                        <div class="mb-3">
                            <label for="motivo_cancelamento" class="form-label">Motivo:</label>
                            <textarea class="form-control" id="motivo_cancelamento" name="motivo_cancelamento" rows="3" required></textarea>
                        </div>
                        <input type="hidden" name="sessao_id" value="ID_DA_SESSAO">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                            <button type="submit" class="btn btn-danger" data-bs-dismiss="modal">Confirmar Cancelamento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLiberarSessao" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Liberar Nova Sessão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Liberar agendamento da próxima sessão para "Fechamento de Braço"?</p>
                    <p class="small text-white-50">O cliente será notificado para agendar a próxima sessão.</p>
                    <form onsubmit="alert('Nova sessão liberada.'); return false;">
                        <input type="hidden" name="projeto_id_liberar" value="102">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                            <button type="submit" class="btn btn-primary" data-bs-dismiss="modal">Liberar Sessão</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalConcluirProjeto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Concluir Projeto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Você confirma que o projeto "Fechamento de Braço" foi <strong>concluído</strong>?</p>
                    <p class="small text-white-50">Ao confirmar, o projeto será movido para o histórico do cliente e não será mais possível agendar novas sessões para ele.</p>
                    <form onsubmit="alert('Projeto concluído.'); return false;">
                        <input type="hidden" name="projeto_id_concluir" value="102">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                            <button type="submit" class="btn btn-success" data-bs-dismiss="modal">Confirmar Conclusão</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    const projetoId = '<?php echo $projeto_id; ?>';
    const tamanhoProjeto = '<?php echo $tamanho; ?>';
    const isArtista = <?php echo $is_artista ? 'true' : 'false'; ?>;

    // --- FUNÇÃO DO CLIENTE (para agendar novos horários) ---
    function mostrarHorarios(event, dataSql, dataBr) {
        event.preventDefault();
        const secaoDetalhes = document.getElementById('secao-detalhes');
        let conteudoHtml = '';

        if (tamanhoProjeto === 'PG') {
            conteudoHtml = `<div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="formulario-container text-center"><h3>Confirmar para ${dataBr}</h3><p class="my-3">O artista reservará o dia inteiro para seu projeto.</p><a href="#" class="btn btn-primary w-100">CONFIRMAR AGENDAMENTO</a></div></div></div>`;
        } else {
            const horariosDisponiveis = ['10:00', '10:30', '14:00', '16:00', '16:30'];
            let listaHorariosHtml = '';
            horariosDisponiveis.forEach(hora => {
                let classeDesabilitada = (tamanhoProjeto === 'PM' && (hora === '10:30' || hora === '16:30')) ? 'disabled' : '';
                listaHorariosHtml += `<a href="#" class="list-group-item list-group-item-action ${classeDesabilitada}">${hora}</a>`;
            });
            conteudoHtml = `<div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="formulario-container text-center"><h3>Horários para ${dataBr}</h3><div id="lista-horarios" class="list-group mt-4">${listaHorariosHtml}</div></div></div></div>`;
        }
        secaoDetalhes.innerHTML = conteudoHtml;
        secaoDetalhes.style.display = 'block';
        secaoDetalhes.scrollIntoView({
            behavior: 'smooth'
        });
    }

    // --- FUNÇÃO DO ARTISTA (para ver detalhes dos dias) (CORRIGIDA) ---
    function mostrarAgendaDia(event, dataSql, dataBr) {
        event.preventDefault();

        // Só executa se for o artista
        if (!isArtista) return;

        const secaoDetalhes = document.getElementById('secao-detalhes');

        let agendamentosDoDia = '';
        let estiloConcluido = 'style="opacity: 0.6; border-left: 4px solid #103e11;"'; // Verde para concluído

        switch (dataSql) {
            // 1. (Concluído) 01/10/2025 - Thábata
            case '2025-10-01':
                agendamentosDoDia = `<div class="list-group-item list-group-item-action flex-column align-items-start" ${estiloConcluido}>
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">10:00 - Dia Todo (Concluído)</h5>
                        <small>PG</small>
                    </div>
                    <p class="mb-1"><strong>Cliente:</strong> Thábata Ricci</p>
                    <small><strong>Projeto:</strong> Fechamento de Braço (Sessão 1)</small>
                </div>`;
                break;

                // 2. (Concluído) 01/11/2025 - Thábata
            case '2025-11-01':
                agendamentosDoDia = `<div class="list-group-item list-group-item-action flex-column align-items-start" ${estiloConcluido}>
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">10:00 - 12:00 (Concluído)</h5>
                        <small>PM (2h)</small>
                    </div>
                    <p class="mb-1"><strong>Cliente:</strong> Thábata Ricci</p>
                    <small><strong>Projeto:</strong> Borboleta</small>
                </div>`;
                break;

                // 3. (Ativo) 08/11/2025 - Thábata
            case '2025-11-08':
                agendamentosDoDia = `<div class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">10:00 - Dia Todo (Agendado)</h5>
                        <small>PG</small>
                    </div>
                    <p class="mb-1"><strong>Cliente:</strong> Thábata Ricci</p>
                    <small><strong>Projeto:</strong> Fechamento de Braço (Sessão 2)</small>
                    <div class="text-end mt-2">
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                    </div>
                </div>`;
                break;

                // 4. (Ativo) 15/11/2025 - Izabella
            case '2025-11-15':
                agendamentosDoDia = `<div class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">15:00 - 17:00 (Agendado)</h5>
                        <small>PM (2h)</small>
                    </div>
                    <p class="mb-1"><strong>Cliente:</strong> Izabella Bianca</p>
                    <small><strong>Projeto:</strong> Tatuagem Harry Potter</small>
                    <div class="text-end mt-2">
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                    </div>
                </div>`;
                break;

                // 5. Dias de Bloqueio Manual
            case '2025-11-20':
            case '2025-11-21':
                agendamentosDoDia = `<div class="list-group-item text-center text-white-50">Dia bloqueado (Folga/Evento).</div>`;
                break;

                // 6. Outros dias (dias livres)
            default:
                agendamentosDoDia = `<div class="list-group-item text-center text-white-50">Nenhum agendamento para este dia.</div>`;
                break;
        }

        const conteudoHtml = `<div class="row justify-content-center">
                                <div class="col-md-8 col-lg-7">
                                    <div class="formulario-container" style="margin-bottom: 0;">
                                        <h3 class="text-center mb-4">Agenda de ${dataBr}</h3>
                                        <div class="list-group">${agendamentosDoDia}</div>
                                    </div>
                                </div>
                              </div>`;

        secaoDetalhes.innerHTML = conteudoHtml;
        secaoDetalhes.style.display = 'block';
        secaoDetalhes.scrollIntoView({
            behavior: 'smooth'
        });
    }

    <?php if ($is_artista): ?>
        // Script para fechar acordeões ao trocar de aba (Apenas para o artista)
        document.addEventListener('DOMContentLoaded', function() {
            var tabs = document.querySelectorAll('#abasAgendaArtista button[data-bs-toggle="tab"]');
            tabs.forEach(function(tab) {
                tab.addEventListener('show.bs.tab', function(event) {

                    var containerId = event.target.getAttribute('data-bs-target');
                    var containerAtivo = document.querySelector(containerId);

                    var openCollapses = document.querySelectorAll('#abasRelatoriosConteudo .accordion-collapse.show');

                    openCollapses.forEach(function(collapse) {

                        if (!containerAtivo.contains(collapse)) {
                            var bsCollapse = new bootstrap.Collapse(collapse, {
                                toggle: false
                            });
                            bsCollapse.hide();
                        }
                    });
                });
            });
        });
    <?php endif; ?>
</script>

<?php
include '../includes/footer.php';
?>