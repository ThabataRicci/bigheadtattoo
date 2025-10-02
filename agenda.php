<?php
session_start();
$titulo_pagina = "Agenda";
include 'includes/header.php';

// --- LÓGICA DO CALENDÁRIO DINÂMICO ---

$ANO_VISUALIZACAO = 2025;
$ANO_ATIVO = 2025;
$MES_ATIVO = 10;

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

if ($ano < $ANO_VISUALIZACAO) {
    $ano = $ANO_VISUALIZACAO;
    $mes = 1;
}

$dias_folga_semana = [0];
$dias_bloqueados_pelo_artista = ['2025-10-20', '2025-10-21', '2025-11-15'];

$primeiro_dia_timestamp = mktime(0, 0, 0, $mes, 1, $ano);
$total_dias_mes = date('t', $primeiro_dia_timestamp);
$primeiro_dia_semana = date('w', $primeiro_dia_timestamp);
$meses_pt = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
$nome_mes_pt = $meses_pt[$mes - 1];

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

function gerar_link_dia($data)
{
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'artista') return 'agenda-artista.php';
        return 'solicitar-agendamento.php?data=' . $data;
    }
    return 'login.php';
}
?>

<main>
    <div class="container my-5 py-5">
        <div class="calendario-container">

            <div class="calendario-header text-center mb-4 d-flex justify-content-between align-items-center">
                <?php if ($ano > $ANO_VISUALIZACAO || ($ano == $ANO_VISUALIZACAO && $mes > 1)): ?>
                    <a href="?mes=<?php echo $mes_anterior; ?>&ano=<?php echo $ano_anterior; ?>" class="btn btn-outline-light">◄</a>
                <?php else: ?>
                    <span style="width: 58px;"></span>
                <?php endif; ?>

                <form method="GET" class="d-flex align-items-center">
                    <select name="mes" class="form-select select-calendario mx-2" onchange="this.form.submit()">
                        <?php foreach ($meses_pt as $num => $nome): ?>
                            <option value="<?php echo $num + 1; ?>" <?php if ($num + 1 == $mes) echo 'selected'; ?>>
                                <?php echo $nome; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="ano" class="form-select select-calendario" onchange="this.form.submit()">
                        <?php for ($a = $ANO_VISUALIZACAO; $a <= $ANO_VISUALIZACAO + 5; $a++): ?>
                            <option value="<?php echo $a; ?>" <?php if ($a == $ano) echo 'selected'; ?>>
                                <?php echo $a; ?>
                            </option>
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
                // Preenche os espaços vazios no início do mês
                for ($i = 0; $i < $primeiro_dia_semana; $i++) {
                    echo '<div class="dia outro-mes"></div>';
                }

                // Gera os dias do mês
                for ($dia = 1; $dia <= $total_dias_mes; $dia++) {
                    $data_atual_formatada = date('Y-m-d', mktime(0, 0, 0, $mes, $dia, $ano));
                    $dia_da_semana_atual = date('w', strtotime($data_atual_formatada));
                    $texto_link = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'artista') ? 'Gerenciar' : 'Solicitar';

                    if ($ano < $ANO_ATIVO || ($ano == $ANO_ATIVO && $mes < $MES_ATIVO)) {
                        echo "<div class='dia dia-ocupado'>$dia</div>";
                    } else {
                        if (in_array($dia_da_semana_atual, $dias_folga_semana) || in_array($data_atual_formatada, $dias_bloqueados_pelo_artista)) {
                            echo "<div class='dia dia-ocupado'>$dia <br><small>Indisponível</small></div>";
                        } else {
                            $link = gerar_link_dia($data_atual_formatada);
                            echo "<a href='{$link}' class='dia dia-livre'>$dia <br><small>{$texto_link}</small></a>";
                        }
                    }
                }

                // Preenche os espaços vazios no final do mês
                $total_celulas = $primeiro_dia_semana + $total_dias_mes;
                while ($total_celulas % 7 != 0) {
                    echo '<div class="dia outro-mes"></div>';
                    $total_celulas++;
                }
                ?>
            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?>