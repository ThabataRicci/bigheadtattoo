<?php
session_start();

$is_artista = (isset($_SESSION['loggedin']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'artista');

$ANO_VISUALIZACAO = 2025;
$ANO_ATIVO = 2025;
$MES_ATIVO = 10;

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

if ($ano < $ANO_VISUALIZACAO) {
    $ano = $ANO_VISUALIZACAO;
    $mes = 1;
}

$dias_folga_semana = [0]; // domingo
$dias_bloqueados_pelo_artista = ['2025-10-20', '2025-10-21', '2025-11-15'];

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
                                <?php if ($ano > $ANO_VISUALIZACAO || ($ano == $ANO_VISUALIZACAO && $mes > 1)): ?>
                                    <a href="?mes=<?php echo $mes_anterior; ?>&ano=<?php echo $ano_anterior; ?>&projeto_id=<?php echo $projeto_id; ?>&tamanho=<?php echo $tamanho; ?>" class="btn btn-outline-light">◄</a>
                                <?php else: ?>
                                    <span style="width: 58px;"></span>
                                <?php endif; ?>
                                <form method="GET" class="d-flex align-items-center">
                                    <select name="mes" class="form-select select-calendario mx-2" onchange="this.form.submit()"><?php foreach ($meses_pt as $num => $nome): ?><option value="<?php echo $num + 1; ?>" <?php if ($num + 1 == $mes) echo 'selected'; ?>><?php echo $nome; ?></option><?php endforeach; ?></select>
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
                                for ($i = 0; $i < $primeiro_dia_semana; $i++) {
                                    echo '<div class="dia outro-mes"></div>';
                                }
                                for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
                                    $data_atual_formatada = date('Y-m-d', mktime(0, 0, 0, $mes, $dia, $ano));
                                    $data_formatada_br = date('d/m/Y', strtotime($data_atual_formatada));
                                    $dia_da_semana_atual = date('w', strtotime($data_atual_formatada));
                                    if ($ano < $ANO_ATIVO || ($ano == $ANO_ATIVO && $mes < $MES_ATIVO) || in_array($dia_da_semana_atual, $dias_folga_semana) || in_array($data_atual_formatada, $dias_bloqueados_pelo_artista)) {
                                        echo "<div class='dia dia-ocupado'>$dia <br><small>Indisponível</small></div>";
                                    } else {
                                        $onclick_action = "mostrarAgendaDia(event, '{$data_atual_formatada}', '{$data_formatada_br}')";
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

                            <div class="text-end mt-4"><button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalDisponibilidade"><i class="bi bi-calendar-x me-2"></i>Gerenciar Bloqueios</button></div>
                        </div>


                        <div id="secao-detalhes" class="mt-5" style="display: none;"></div>

                    </div>


                    <div class="tab-pane fade" id="tab-solicitacoes" role="tabpanel" aria-labelledby="solicitacoes-tab">
                        <?php // SIMULAÇÃO DE DADOS 
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
                                                <span><strong>Cliente:</strong> Izabella Bianca | <strong>Ideia:</strong> Fechamento de costas</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="item1" class="accordion-collapse collapse" data-bs-parent="#acordeaoSolicitacoes">
                                        <div class="accordion-body">
                                            <p><strong>Local do Corpo:</strong> Costas</p>
                                            <p><strong>Tamanho Aproximado:</strong> Fechamento</p>
                                            <p><strong>Ideia do Cliente:</strong> "Gostaria de iniciar um projeto de fechamento de costas com um dragão oriental..."</p>
                                            <p><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_imagem_dragao.jpg</a></p>
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
                        <?php // SIMULAÇÃO DE DADOS 
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
                                                <span><strong>Cliente:</strong> João Silva | <strong>Projeto:</strong> Tatuagem Fineline</span>
                                                <span class="me-3"><strong>Data:</strong> 20/10/2025 às 11:00</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="sessaoPP" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                                        <div class="accordion-body">
                                            <p class="text-white-50 mb-2"><strong>Detalhes:</strong></p>
                                            <ul class="list-unstyled card-resumo p-3 small">
                                                <li><strong>Local do Corpo:</strong> Pulso</li>
                                                <li><strong>Tamanho Aproximado:</strong> Pequeno (até 10cm)</li>
                                                <li><strong>Ideia do Cliente:</strong> "Uma pequena âncora em fineline no pulso."</li>
                                                <li><strong>Referência Enviada:</strong> Nenhuma</li>
                                                <li><strong>Duração da Sessão:</strong> 30 minutos</li>
                                            </ul>
                                            <div class="text-end mt-3">
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessaoPG">
                                            <div class="w-100 d-flex justify-content-between align-items-center">
                                                <span><strong>Cliente:</strong> Maria Oliveira | <strong>Projeto:</strong> Fechamento de Perna</span>
                                                <span class="me-3"><strong>Data:</strong> 28/10/2025 às 10:00</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="sessaoPG" class="accordion-collapse collapse" data-bs-parent="#acordeaoSessoesAgendadas">
                                        <div class="accordion-body">
                                            <p class="text-white-50 mb-2"><strong>Detalhes:</strong></p>
                                            <ul class="list-unstyled card-resumo p-3 small">
                                                <li><strong>Local do Corpo:</strong> Perna</li>
                                                <li><strong>Tamanho Aproximado:</strong> Fechamento</li>
                                                <li><strong>Ideia do Cliente:</strong> "Projeto para fechar a perna."</li>
                                                <li><strong>Referência Enviada:</strong> <a href="#" class="text-white-50">ver_referencia.jpg</a></li>
                                                <li><strong>Duração da Sessão:</strong> Dia Todo</li>
                                            </ul>
                                            <p class="text-white-50 mb-2 mt-4"><strong>Histórico de Sessões:</strong></p>
                                            <div class="card-resumo p-3">
                                                <div class="d-flex justify-content-between align-items-center small p-2">
                                                    <span><strong>Sessão 1:</strong> Concluída em 01/10/2025</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center small p-2">
                                                    <span><strong>Sessão 2:</strong> Agendada para 28/10/2025 às 10:00</span>
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelar">Cancelar Sessão</button>
                                                </div>
                                            </div>
                                            <div class="text-end mt-3">
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalLiberarSessao">Liberar Nova Sessão</button>
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
                        <?php if ($ano > $ANO_VISUALIZACAO || ($ano == $ANO_VISUALIZACAO && $mes > 1)): ?>
                            <a href="?mes=<?php echo $mes_anterior; ?>&ano=<?php echo $ano_anterior; ?>&projeto_id=<?php echo $projeto_id; ?>&tamanho=<?php echo $tamanho; ?>" class="btn btn-outline-light">◄</a>
                        <?php else: ?>
                            <span style="width: 58px;"></span>
                        <?php endif; ?>
                        <form method="GET" class="d-flex align-items-center">
                            <select name="mes" class="form-select select-calendario mx-2" onchange="this.form.submit()"><?php foreach ($meses_pt as $num => $nome): ?><option value="<?php echo $num + 1; ?>" <?php if ($num + 1 == $mes) echo 'selected'; ?>><?php echo $nome; ?></option><?php endforeach; ?></select>
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
                        for ($i = 0; $i < $primeiro_dia_semana; $i++) {
                            echo '<div class="dia outro-mes"></div>';
                        }
                        for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
                            $data_atual_formatada = date('Y-m-d', mktime(0, 0, 0, $mes, $dia, $ano));
                            $data_formatada_br = date('d/m/Y', strtotime($data_atual_formatada));
                            $dia_da_semana_atual = date('w', strtotime($data_atual_formatada));
                            if ($ano < $ANO_ATIVO || ($ano == $ANO_ATIVO && $mes < $MES_ATIVO) || in_array($dia_da_semana_atual, $dias_folga_semana) || in_array($data_atual_formatada, $dias_bloqueados_pelo_artista)) {
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
                            <p class="small text-white-50 mb-1">Datas já bloqueadas:</p>
                            <ul class="list-group">
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">20/10/2025<button class="btn btn-sm btn-danger py-0 px-2">&times;</button></li>
                                <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">21/10/2025<button class="btn btn-sm btn-danger py-0 px-2">&times;</button></li>
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
                    <form onsubmit="alert('Projeto recusado (simulação).'); return false;">
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
                    <form onsubmit="alert('Sessão cancelada (simulação).'); return false;">
                        <div class="mb-3">
                            <label for="motivo_cancelamento" class="form-label">Motivo:</label>
                            <textarea class="form-control" id="motivo_cancelamento" name="motivo_cancelamento" rows="3" required></textarea>
                        </div>
                        <input type="hidden" name="sessao_id" value="201">
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
                    <p>Você confirma que o projeto "Fechamento de Perna" de Maria Oliveira está pronto para a próxima sessão?</p>
                    <p class="small text-white-50">Ao confirmar, o cliente será notificado para agendar a próxima sessão (Sessão 3).</p>
                    <form onsubmit="alert('Nova sessão liberada (simulação).'); return false;">
                        <input type="hidden" name="projeto_id_liberar" value="ID_DO_PROJETO_MARIA">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                            <button type="submit" class="btn btn-primary" data-bs-dismiss="modal">Liberar Sessão</button>
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

    function mostrarAgendaDia(event, dataSql, dataBr) {
        event.preventDefault();
        const secaoDetalhes = document.getElementById('secao-detalhes');


        let agendamentosDoDia = '';
        if (dataSql === '2025-10-28') {
            agendamentosDoDia = `<div class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">10:00 - Dia Todo</h5>
                    <small>PG</small>
                </div>
                <p class="mb-1"><strong>Cliente:</strong> Maria Oliveira</p>
                <small><strong>Projeto:</strong> Fechamento de Perna</small>
            </div>`;
        } else if (dataSql === '2025-10-20') {
            agendamentosDoDia = `<div class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">11:00 - 11:30</h5>
                    <small>PP (30min)</small>
                </div>
                <p class="mb-1"><strong>Cliente:</strong> João Silva</p>
                <small><strong>Projeto:</strong> Tatuagem Fineline</small>
            </div>`;
        } else {
            agendamentosDoDia = `<div class="list-group-item text-center text-white-50">Nenhum agendamento para este dia.</div>`;
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