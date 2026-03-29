<?php
session_start();
require_once '../includes/conexao.php';

// apenas clientes podem acessar esta tela
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];
$projeto_id = isset($_GET['projeto_id']) ? (int)$_GET['projeto_id'] : 0;

// se não tiver projeto na URL, manda de volta pros agendamentos
if ($projeto_id === 0) {
    header("Location: agendamentos-cliente.php");
    exit();
}

// 1. busca os dados do projeto para saber a duração
try {
    $stmt_proj = $pdo->prepare("SELECT p.titulo, o.estimativa_tempo FROM projeto p LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento WHERE p.id_projeto = ? AND p.id_usuario = ? AND p.status = 'Agendamento Pendente'");
    $stmt_proj->execute([$projeto_id, $id_usuario_logado]);
    $projeto = $stmt_proj->fetch();

    if (!$projeto) {
        header("Location: agendamentos-cliente.php");
        exit();
    }
    $estimativa_tempo_projeto = $projeto['estimativa_tempo'] ?: 'Projeto Pequeno (Até 2h)';
    $titulo_do_projeto = $projeto['titulo'];
} catch (PDOException $e) {
    exit("Erro ao buscar projeto.");
}

// 2. busca o ID do artista 
$stmt_art = $pdo->query("SELECT id_usuario FROM usuario WHERE perfil = 'artista' LIMIT 1");
$id_artista = $stmt_art->fetchColumn();

// 3. busca os bloqueios de agenda do artista 
$dias_bloqueados_manualmente = [];
if ($id_artista) {
    $stmt_bloq = $pdo->prepare("SELECT data_bloqueio FROM bloqueio_agenda WHERE id_artista = ?");
    $stmt_bloq->execute([$id_artista]);
    foreach ($stmt_bloq->fetchAll() as $b) {
        $dias_bloqueados_manualmente[] = $b['data_bloqueio'];
    }
}

// 4. busca os horários já ocupados pelo artista
$horarios_ocupados = [];
$dias_com_agendamento = [];
$dias_minhas_sessoes = [];
$detalhes_minhas_sessoes = [];

$stmt_busy = $pdo->query("SELECT s.data_hora, o.estimativa_tempo, p.id_usuario, p.titulo FROM sessao s JOIN projeto p ON s.id_projeto = p.id_projeto LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento WHERE s.status = 'Agendado' AND s.data_hora >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
foreach ($stmt_busy->fetchAll() as $b) {
    $data_apenas = date('Y-m-d', strtotime($b['data_hora']));
    $hora_apenas = date('H:i', strtotime($b['data_hora']));

    $dias_com_agendamento[] = $data_apenas;

    if ($b['id_usuario'] == $id_usuario_logado) {
        $dias_minhas_sessoes[] = $data_apenas;
        if (!isset($detalhes_minhas_sessoes[$data_apenas])) {
            $detalhes_minhas_sessoes[$data_apenas] = [];
        }
        $detalhes_minhas_sessoes[$data_apenas][] = [
            'hora' => $hora_apenas,
            'titulo' => htmlspecialchars($b['titulo'])
        ];
    }

    $duracao = 2;
    if (strpos($b['estimativa_tempo'], 'Médio') !== false) $duracao = 4;
    if (strpos($b['estimativa_tempo'], 'Grande') !== false) $duracao = 6;
    if (strpos($b['estimativa_tempo'], 'Todo') !== false) $duracao = 8;

    $horarios_ocupados[$data_apenas][] = [
        'hora' => $hora_apenas,
        'duracao' => $duracao
    ];
}

// LÓGICA DE DURAÇÃO 
$duracao_necessaria = 2;
if (strpos($estimativa_tempo_projeto, 'Médio') !== false) $duracao_necessaria = 4;
if (strpos($estimativa_tempo_projeto, 'Grande') !== false) $duracao_necessaria = 6;
if (strpos($estimativa_tempo_projeto, 'Todo') !== false) $duracao_necessaria = 8;

// Dias fixos para o calendário (0 = Domingo)
$dias_folga_semana = [0];

// Configurações do Calendário
$ANO_VISUALIZACAO = 2026;
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

// Pega a data real de hoje
$data_hoje_formatada = date('Y-m-d');

$titulo_pagina = "Escolha o Dia e Horário";
include '../includes/header.php';
?>

<div class="submenu-painel">
    <a href="dashboard-cliente.php" class="">Início</a>
    <a href="agendamentos-cliente.php" class="active">Meus Agendamentos</a>
    <a href="solicitar-orcamento.php" class="">Orçamento</a>
    <a href="configuracoes-cliente.php" class="">Configurações</a>
</div>

<style>
    .dia-minha-sessao {
        color: #a0a0a0 !important;
        position: relative;
        background-color: #2C2C2C !important;
        border: 1px solid #444;
        transition: all 0.3s ease;
    }

    .dia-minha-sessao:hover {
        background-color: #3a3a3a !important;
        border-color: #959595;
        color: #ffffff !important;
        cursor: pointer;
    }

    .dia-minha-sessao::before {
        content: '';
        position: absolute;
        top: 6px;
        right: 6px;
        width: 10px;
        height: 10px;
        background-color: #ffffff;
        border-radius: 50%;
    }

    .calendario-wrapper {
        margin: 0 auto;
        background-color: #1C1C1C;
        border: 1px solid #444;
        border-radius: 12px;
        padding: 25px;
    }

    .dia-inativo {
        background-color: #2a2a2a !important;
        color: #555 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
    }
</style>

<main>
    <div class="container my-5 py-5">
        <div class="text-center mb-5">
            <h2 class="text-light mb-3"><?php echo htmlspecialchars($titulo_do_projeto); ?></h2>
            <p class="text-white-50">Selecione uma data para agendar sua sessão.</p>
        </div>

        <div class="calendario-wrapper shadow">
            <div class="calendario-container p-0" style="border: none; background: none; margin-bottom: 0;">
                <div class="calendario-header text-center mb-4 d-flex justify-content-between align-items-center">
                    <?php
                    $link_extra = "&projeto_id={$projeto_id}";
                    $href_anterior = "?mes={$mes_anterior}&ano={$ano_anterior}{$link_extra}";
                    $classe_anterior = "btn btn-outline-light btn-sm";
                    if ($ano == $ANO_VISUALIZACAO && $mes == $MES_ATIVO) {
                        $href_anterior = "#";
                        $classe_anterior = "btn btn-outline-light btn-sm disabled";
                    }
                    ?>
                    <a href="<?php echo $href_anterior; ?>" class="<?php echo $classe_anterior; ?>">◄</a>

                    <form method="GET" class="d-flex align-items-center">
                        <input type="hidden" name="projeto_id" value="<?php echo $projeto_id; ?>">
                        <select name="mes" class="form-select form-select-sm select-calendario mx-2" onchange="this.form.submit()">
                            <?php foreach ($meses_pt as $num => $nome):
                                if ($ano == $ANO_VISUALIZACAO && $num + 1 < $MES_ATIVO) continue; ?>
                                <option value="<?php echo $num + 1; ?>" <?php if ($num + 1 == $mes) echo 'selected'; ?>><?php echo $nome; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="ano" class="form-select form-select-sm select-calendario" onchange="this.form.submit()">
                            <?php for ($a = $ANO_VISUALIZACAO; $a <= $ANO_VISUALIZACAO + 5; $a++): ?>
                                <option value="<?php echo $a; ?>" <?php if ($a == $ano) echo 'selected'; ?>><?php echo $a; ?></option>
                            <?php endfor; ?>
                        </select>
                    </form>
                    <a href="?mes=<?php echo $mes_proximo; ?>&ano=<?php echo $ano_proximo; ?><?php echo $link_extra; ?>" class="btn btn-outline-light btn-sm">►</a>
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
                        $onclick_action = "mostrarHorarios(event, '{$data_atual_formatada}', '{$data_formatada_br}')";

                        // verifica se é passado, domingo ou bloqueado manual
                        $is_passado = ($data_atual_formatada < $data_hoje_formatada);
                        $is_bloqueado = ($dia_da_semana_atual == 0 || in_array($data_atual_formatada, $dias_bloqueados_manualmente));

                        // se estiver inativo, usa uma <div> vazia 
                        if ($is_passado || $is_bloqueado) {
                            echo "<div class='dia dia-inativo'>$dia</div>";
                        } else {
                            $extra_class = '';

                            // 1. Marca visualmente se o dia já é uma sessão do próprio cliente
                            if (in_array($data_atual_formatada, $dias_minhas_sessoes)) {
                                $extra_class .= ' dia-minha-sessao';
                            }
                            // 2. Apenas define como dia livre (sem marcação para terceiros)
                            else {
                                $extra_class .= ' dia-livre';
                            }

                            if ($data_atual_formatada == $data_hoje_formatada) {
                                $extra_class .= ' dia-hoje';
                            }

                            // 3. Simula se o dia comporta o tamanho da sessão
                            $tem_vaga = false;
                            $slots_teste = ($duracao_necessaria <= 2) ? [9, 11, 14, 16] : (($duracao_necessaria <= 4) ? [9, 14] : [10]);

                            foreach ($slots_teste as $slotHora) {
                                $slotFim = $slotHora + $duracao_necessaria;
                                $conflito = false;
                                if (isset($horarios_ocupados[$data_atual_formatada])) {
                                    foreach ($horarios_ocupados[$data_atual_formatada] as $ocup) {
                                        $oHora = (int)explode(':', $ocup['hora'])[0];
                                        $oFim = $oHora + $ocup['duracao'];
                                        if (($slotHora < $oFim) && ($slotFim > $oHora)) {
                                            $conflito = true;
                                            break;
                                        }
                                    }
                                }
                                if (!$conflito) {
                                    $tem_vaga = true;
                                    break;
                                }
                            }

                            if ($tem_vaga) {
                                // Se tem vaga, exibe o dia clicável normalmente
                                echo "<a href='#' onclick=\"{$onclick_action}\" class='dia{$extra_class}'>$dia</a>";
                            } else {
                                // Se NÃO tem vaga, trata como dia inativo (não clicável)
                                echo "<div class='dia dia-inativo'>$dia</div>";
                            }
                        }
                    }

                    $total_celulas = $primeiro_dia_semana + $total_dias_mes;
                    while ($total_celulas % 7 != 0) {
                        echo "<div class='dia outro-mes'></div>";
                        $total_celulas++;
                    }
                    ?>
                </div>
            </div>
        </div>

        <div id="secao-detalhes" class="mt-4" style="display: none;"></div>

        <div class="d-flex justify-content-end mt-5">
            <a href="agendamentos-cliente.php" class="btn btn-outline-secondary">Meus Agendamentos</a>
        </div>
    </div>
</main>

<div class="modal fade" id="modalConfirmarAgendamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark border-secondary">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-success">Confirmar Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="text-white-50 mb-2">Agendar sessão para:</p>
                <h3 class="text-light mb-4" id="displayDataHora">--</h3>
                <p class="small text-white-50">Após confirmar, o artista será notificado da data escolhida.</p>

                <form action="../actions/a.agendar-sessao.php" method="POST" onsubmit="let btnSubmit = this.querySelector('button[type=submit]'); btnSubmit.disabled = true; btnSubmit.innerHTML = '<span class=\'spinner-border spinner-border-sm me-2\'></span>Agendando...';">
                    <input type="hidden" name="projeto_id" value="<?php echo $projeto_id; ?>">
                    <input type="hidden" name="data_sessao" id="inputDataSessao" value="">
                    <input type="hidden" name="hora_sessao" id="inputHoraSessao" value="">

                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Agendar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const diasBloqueados = <?php echo json_encode($dias_bloqueados_manualmente); ?>;
    const horariosOcupados = <?php echo json_encode($horarios_ocupados ?? []); ?>;
    const estimativaTempo = "<?php echo $estimativa_tempo_projeto; ?>";
    const projetoIdCliente = <?php echo $projeto_id; ?>;
    const minhasSessoesDetalhes = <?php echo json_encode($detalhes_minhas_sessoes ?? new stdClass()); ?>;

    function mostrarHorarios(event, dataSql, dataBr) {
        event.preventDefault();
        const secaoDetalhes = document.getElementById('secao-detalhes');

        // monta o aviso se o cliente já tiver sessões marcadas nesse dia ---
        let avisosSessoes = '';
        if (minhasSessoesDetalhes[dataSql]) {
            const sessoesDia = minhasSessoesDetalhes[dataSql];
            sessoesDia.forEach(sessao => {
                avisosSessoes += `
                    <div class="alert bg-dark border border-light text-start text-light mb-4 shadow-sm" style="border-radius: 8px;">
                        <h6 class="mb-2"><i class="bi bi-calendar-check me-2"></i>Você já tem um agendamento nessa data:</h6>
                        <p class="mb-1 small"><strong>Projeto:</strong> ${sessao.titulo}</p>
                        <p class="mb-0 small"><strong>Horário:</strong> ${sessao.hora}</p>
                        <p class="small text-white-50 mb-2 fs-7"><i class="bi bi-info-circle me-1 fs-7"></i>Isso não impede que você agende uma nova sessão</p>
                    </div>
                `;
            });
        }

        let duracaoNecessaria = 2;
        if (estimativaTempo.includes('Médio')) duracaoNecessaria = 4;
        if (estimativaTempo.includes('Grande')) duracaoNecessaria = 6;
        if (estimativaTempo.includes('Todo')) duracaoNecessaria = 8;

        let slots = [];
        if (duracaoNecessaria <= 2) {
            // projetos de até 2h: evita o almoço (12h-13h) e não passa das 18h.
            slots = ["09:00", "10:00", "13:00", "14:00", "15:00", "16:00"];
        } else if (duracaoNecessaria <= 4) {
            // projetos de 4h: de manhã invadiria o almoço (9h as 13h). então só podem ocorrer a tarde, não passando das 18h
            slots = ["13:00", "14:00"];
        } else {
            // projetos de 6h ou 8h (Fechamento): ocupam praticamente o dia todo.
            slots = ["09:00"];
        }

        let slotsFiltrados = slots.filter(slot => {
            let slotHora = parseInt(slot.split(':')[0]);
            let slotFim = slotHora + duracaoNecessaria;

            const horaAlmoco = 12;
            const horaFimExpediente = 18;

            // aplica as regras de bloqueio apenas para sessões curtas e médias
            if (duracaoNecessaria <= 4) {
                // 1. não pode passar das 18h
                if (slotFim > horaFimExpediente) return false;

                // 2. não pode invadir o almoço (começar antes das 12h e terminar depois)
                if (slotHora < horaAlmoco && slotFim > horaAlmoco) return false;

                // 3. não pode começar exatamente dentro da hora de almoço
                if (slotHora === horaAlmoco) return false;
            }

            // CONFLITO COM OUTROS AGENDAMENTOS 
            let conflito = false;
            if (horariosOcupados[dataSql]) {
                horariosOcupados[dataSql].forEach(ocup => {
                    let oHora = parseInt(ocup.hora.split(':')[0]);
                    let oFim = oHora + ocup.duracao;

                    if ((slotHora < oFim) && (slotFim > oHora)) {
                        conflito = true;
                    }
                });
            }
            return !conflito;
        });

        let botoesHtml = '';
        if (slotsFiltrados.length === 0) {
            botoesHtml = `<div class="bg-dark text-white text-center w-100 m-0 p-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Não há horários disponíveis, escolha outro dia.
</div>`;
        } else {
            slotsFiltrados.forEach(slot => {
                botoesHtml += `
                    <button type="button" class="btn btn-outline-success btn-lg px-4 m-2 shadow-sm btn-escolher-horario" 
                        data-datasql="${dataSql}" 
                        data-databr="${dataBr}" 
                        data-hora="${slot}" 
                        data-bs-toggle="modal" 
                        data-bs-target="#modalConfirmarAgendamento">
                        <i class="bi bi-clock me-2"></i> ${slot}
                    </button>
                `;
            });
        }

        secaoDetalhes.innerHTML = `
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="formulario-container p-4 border border-secondary shadow text-center" style="margin-bottom: 0; max-width: 500px; margin: 0 auto; background-color: #1C1C1C;">
                        ${avisosSessoes}
                        <h4 class="mb-2 text-light">${dataBr}</h4>
                        <p class="mb-2">${estimativaTempo}</p>
                        <div class="d-flex justify-content-center flex-wrap">
                            ${botoesHtml}
                        </div>
                    </div>
                </div>
            </div>
        `;
        secaoDetalhes.style.display = 'block';
        secaoDetalhes.scrollIntoView({
            behavior: 'smooth'
        });

        document.querySelectorAll('.btn-escolher-horario').forEach(btn => {
            btn.addEventListener('click', function() {
                const dataSql = this.getAttribute('data-datasql');
                const dataBr = this.getAttribute('data-databr');
                const hora = this.getAttribute('data-hora');

                document.getElementById('inputDataSessao').value = dataSql;
                document.getElementById('inputHoraSessao').value = hora;
                document.getElementById('displayDataHora').innerText = dataBr + " | " + hora;
            });
        });
    }
</script>

<?php include '../includes/footer.php'; ?>