<?php
session_start();

// --- DETECÇÃO DE QUEM ESTÁ LOGADO (CLIENTE OU ARTISTA) ---
$is_artista = (isset($_SESSION['loggedin']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'artista');

$titulo_pagina = $is_artista ? "Agenda" : "Escolha o Dia e Horário";
include '../includes/header.php';

// --- LÓGICA DO CALENDÁRIO COMPLETA (COM NAVEGAÇÃO) ---
$ANO_VISUALIZACAO = 2025;
$ANO_ATIVO = 2025;
$MES_ATIVO = 10;

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

if ($ano < $ANO_VISUALIZACAO) {
    $ano = $ANO_VISUALIZACAO;
    $mes = 1;
}

$dias_folga_semana = [0]; // Domingo
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
?>

<main>
    <div class="container my-5 py-5">
        <div class="text-center mb-4">
            <h2><?php echo $is_artista ? "AGENDA" : "AGENDAR SESSÃO"; ?></h2>
            <p class="text-white-50"><?php echo $is_artista ? "Clique em um dia para ver os agendamentos ou gerenciar bloqueios." : "Selecione uma data disponível para ver os horários."; ?></p>
        </div>

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
                        // AQUI ESTÁ A CORREÇÃO PRINCIPAL: Passando o 'event' para a função
                        $onclick_action = $is_artista
                            ? "mostrarAgendaDia(event, '{$data_atual_formatada}', '{$data_formatada_br}')"
                            : "mostrarHorarios(event, '{$data_atual_formatada}', '{$data_formatada_br}')";
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

            <?php if ($is_artista): ?>
                <div class="text-end mt-4"><button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalDisponibilidade"><i class="bi bi-calendar-x me-2"></i>Gerenciar Bloqueios</button></div>
            <?php endif; ?>
        </div>

        <div id="secao-detalhes" class="mt-5" style="display: none;"></div>
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
<?php endif; ?>

<script>
    const projetoId = '<?php echo $projeto_id; ?>';
    const tamanhoProjeto = '<?php echo $tamanho; ?>';
    const isArtista = <?php echo $is_artista ? 'true' : 'false'; ?>;

    // AQUI ESTÁ A SEGUNDA CORREÇÃO: Recebendo o 'event' como parâmetro
    function mostrarHorarios(event, dataSql, dataBr) {
        event.preventDefault(); // Impede que a página recarregue
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
        const conteudoHtml = `<div class="row justify-content-center"><div class="col-md-8 col-lg-7"><div class="formulario-container"><h3 class="text-center mb-4">Agenda de ${dataBr}</h3><div class="list-group"><div class="list-group-item list-group-item-action flex-column align-items-start"><div class="d-flex w-100 justify-content-between"><h5 class="mb-1">10:00 - 12:00</h5><small>PM (2h)</small></div><p class="mb-1"><strong>Cliente:</strong> Maria Oliveira</p><small><strong>Projeto:</strong> Fechamento de Perna</small></div><div class="list-group-item list-group-item-action flex-column align-items-start"><div class="d-flex w-100 justify-content-between"><h5 class="mb-1">14:00 - 14:30</h5><small>PP (30min)</small></div><p class="mb-1"><strong>Cliente:</strong> João Silva</p><small><strong>Projeto:</strong> Tatuagem Fineline</small></div></div></div></div></div>`;
        secaoDetalhes.innerHTML = conteudoHtml;
        secaoDetalhes.style.display = 'block';
        secaoDetalhes.scrollIntoView({
            behavior: 'smooth'
        });
    }
</script>

<?php
include '../includes/footer.php';
?>