<?php
session_start();
require_once '../includes/conexao.php'; // conexao com o Clever Cloud

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'cliente') {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$titulo_pagina = "Meus Agendamentos";
include '../includes/header.php';

// logica do banco de dados
$projetos_para_agendar = [];
$proximas_sessoes = [];
$orcamentos_pendentes = [];
$historico = [];

// 1. buscar orçamentos pendentes (em análise)
try {
    $sql_pendentes = "SELECT * FROM orcamento WHERE id_usuario = ? AND (status = 'Pendente' OR status IS NULL)";
    $stmt = $pdo->prepare($sql_pendentes);
    $stmt->execute([$id_usuario]);
    $resultados_pendentes = $stmt->fetchAll();

    foreach ($resultados_pendentes as $row) {
        $ideia_completa = htmlspecialchars($row['descricao_ideia']);
        $orcamentos_pendentes[] = [
            'id' => $row['id_orcamento'],
            'titulo' => mb_strimwidth($ideia_completa, 0, 30, "..."),
            'status' => 'Aguardando Análise',
            'status_class' => 'status-analise',
            'local' => htmlspecialchars($row['local_corpo']),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
            'ideia' => '"' . $ideia_completa . '"',
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'detalhe_status' => 'Sua ideia foi enviada e está com o artista para análise.'
        ];
    }
} catch (PDOException $e) {
}

// 2. buscar próximas sessões agendadas (COM HISTÓRICO DINÂMICO)
try {
    $sql_sessoes = "SELECT s.id_sessao, s.data_hora, p.titulo, p.id_projeto, o.local_corpo, o.tamanho_aproximado, o.descricao_ideia, o.estimativa_tempo, o.referencia_ideia, o.qtd_sessoes 
                    FROM sessao s 
                    JOIN projeto p ON s.id_projeto = p.id_projeto 
                    LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento
                    WHERE p.id_usuario = ? AND s.status = 'Agendado' AND s.data_hora >= NOW() 
                    ORDER BY s.data_hora ASC";
    $stmt = $pdo->prepare($sql_sessoes);
    $stmt->execute([$id_usuario]);
    foreach ($stmt->fetchAll() as $row) {
        $data_obj = new DateTime($row['data_hora']);

        // BUSCA O HISTÓRICO DE SESSÕES DESTE PROJETO
        $stmt_hist = $pdo->prepare("SELECT id_sessao, data_hora, status FROM sessao WHERE id_projeto = ? AND status != 'Cancelado' ORDER BY data_hora ASC");
        $stmt_hist->execute([$row['id_projeto']]);
        $sessoes_do_projeto = $stmt_hist->fetchAll();

        $historico_montado = [];
        $contador = 1;
        foreach ($sessoes_do_projeto as $h) {
            $d = new DateTime($h['data_hora']);
            if ($h['status'] == 'Concluído') {
                $historico_montado[] = ['desc' => "{$contador}ª Sessão: Concluída em " . $d->format('d/m/Y'), 'pode_cancelar' => false, 'icone' => 'bi-check-circle-fill text-success'];
            } elseif ($h['status'] == 'Agendado') {
                $pode = ($h['id_sessao'] == $row['id_sessao']);
                $historico_montado[] = ['desc' => "{$contador}ª Sessão: Agendada para " . $d->format('d/m/Y H:i'), 'pode_cancelar' => $pode, 'icone' => 'bi-calendar-event text-info'];
            }
            $contador++;
        }

        $proximas_sessoes[] = [
            'id' => $row['id_sessao'],
            'id_projeto' => $row['id_projeto'],
            'titulo' => htmlspecialchars($row['titulo']),
            'data' => $data_obj->format('d/m/Y \à\s H:i'),
            'local' => htmlspecialchars($row['local_corpo'] ?? 'Não informado'),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado'] ?? 'Não informado'),
            'ideia' => htmlspecialchars($row['descricao_ideia'] ?? 'Sessão confirmada.'),
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'duracao' => htmlspecialchars($row['estimativa_tempo'] ?? 'A definir'),
            'sessoes_estimadas' => htmlspecialchars($row['qtd_sessoes'] ?? '-'),
            'historico_sessoes' => $historico_montado
        ];
    }

    // 3. ação requerida: buscar orçamentos aprovados
    $sql_aprovados = "SELECT * FROM orcamento WHERE id_usuario = ? AND status = 'Aprovado' AND id_orcamento NOT IN (SELECT id_orcamento FROM projeto WHERE id_usuario = ? AND id_orcamento IS NOT NULL)";
    $stmt = $pdo->prepare($sql_aprovados);
    $stmt->execute([$id_usuario, $id_usuario]);
    foreach ($stmt->fetchAll() as $row) {
        $ideia_completa = htmlspecialchars($row['descricao_ideia']);
        $projetos_para_agendar[] = [
            'id_orcamento' => $row['id_orcamento'],
            'titulo' => mb_strimwidth($ideia_completa, 0, 30, "..."),
            'status' => 'Agende sua sessão',
            'status_class' => 'status-acao',
            'local' => htmlspecialchars($row['local_corpo']),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
            'ideia' => '"' . $ideia_completa . '"',
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'duracao' => !empty($row['estimativa_tempo']) ? htmlspecialchars($row['estimativa_tempo']) : 'A definir',
            'sessoes_estimadas' => !empty($row['qtd_sessoes']) ? htmlspecialchars($row['qtd_sessoes']) : 'A definir',
            'motivo_reagendamento' => null
        ];
    }

    // 3.B ação requerida: buscar projetos aguardando agendamento/reagendamento
    $sql_reagendar = "SELECT p.*, o.local_corpo, o.tamanho_aproximado, o.descricao_ideia, o.estimativa_tempo, o.qtd_sessoes 
                      FROM projeto p 
                      LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento 
                      WHERE p.id_usuario = ? AND p.status = 'Agendamento Pendente'";
    $stmt = $pdo->prepare($sql_reagendar);
    $stmt->execute([$id_usuario]);
    foreach ($stmt->fetchAll() as $row) {

        // Busca o histórico de sessões pra exibir mesmo na aba de pendências
        $stmt_hist = $pdo->prepare("SELECT id_sessao, data_hora, status FROM sessao WHERE id_projeto = ? AND status != 'Cancelado' ORDER BY data_hora ASC");
        $stmt_hist->execute([$row['id_projeto']]);
        $sessoes_do_projeto = $stmt_hist->fetchAll();

        $historico_montado = [];
        $contador = 1;
        foreach ($sessoes_do_projeto as $h) {
            $d = new DateTime($h['data_hora']);
            if ($h['status'] == 'Concluído') {
                $historico_montado[] = ['desc' => "{$contador}ª Sessão: Concluída em " . $d->format('d/m/Y'), 'icone' => 'bi-check-circle-fill text-success'];
            }
            $contador++;
        }

        // Inteligência: Se tem motivo, é Reagendar. Se não tem, é Agendar nova etapa.
        $is_reagendamento = !empty($row['motivo_reagendamento']);

        $projetos_para_agendar[] = [
            'id_projeto' => $row['id_projeto'],
            'titulo' => htmlspecialchars($row['titulo']),
            'status' => $is_reagendamento ? 'Reagendar' : 'Agendar Sessão',
            'status_class' => 'status-acao',
            'local' => htmlspecialchars($row['local_corpo'] ?? 'Não informado'),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado'] ?? 'Não informado'),
            'ideia' => htmlspecialchars($row['descricao_ideia'] ?? 'Escolha a nova data.'),
            'ref' => 'Sem referência',
            'duracao' => htmlspecialchars($row['estimativa_tempo'] ?? 'A definir'),
            'sessoes_estimadas' => htmlspecialchars($row['qtd_sessoes'] ?? '-'),
            'motivo_reagendamento' => $row['motivo_reagendamento'],
            'historico_sessoes' => $historico_montado
        ];
    }
} catch (PDOException $e) {
}

// 4 e 5. histórico 
try {
    // orçamentos recusados
    $sql_recusados = "SELECT * FROM orcamento WHERE id_usuario = ? AND status = 'Recusado'";
    $stmt = $pdo->prepare($sql_recusados);
    $stmt->execute([$id_usuario]);
    foreach ($stmt->fetchAll() as $row) {
        $ideia_completa = htmlspecialchars($row['descricao_ideia']);
        $historico[] = [
            'tipo' => 'recusado',
            'titulo' => mb_strimwidth($ideia_completa, 0, 30, "..."),
            'status' => 'Recusado',
            'status_class' => 'status-cancelado',
            'local' => htmlspecialchars($row['local_corpo']),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
            'ideia' => '"' . $ideia_completa . '"',
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'detalhe_status' => !empty($row['motivo_recusa']) ? htmlspecialchars($row['motivo_recusa']) : 'O artista avaliou sua ideia, mas não poderá realizá-la no momento.',
            'data_sort' => $row['data_envio'] ?? '1970-01-01 00:00:00' // Guarda a data para o filtro
        ];
    }

    // projetos finalizados ou cancelados
    $sql_hist_proj = "SELECT p.*, o.local_corpo, o.tamanho_aproximado, o.descricao_ideia, o.qtd_sessoes 
                      FROM projeto p 
                      LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento
                      WHERE p.id_usuario = ? AND p.status IN ('Finalizado', 'Cancelado')
                      ORDER BY p.id_projeto DESC";
    $stmt = $pdo->prepare($sql_hist_proj);
    $stmt->execute([$id_usuario]);
    foreach ($stmt->fetchAll() as $row) {

        // busca todas as sessoes do projeto
        $stmt_hist = $pdo->prepare("SELECT data_hora, status, motivo_cancelamento FROM sessao WHERE id_projeto = ? ORDER BY data_hora ASC");
        $stmt_hist->execute([$row['id_projeto']]);
        $sessoes_do_projeto = $stmt_hist->fetchAll();

        $historico_montado = [];
        $contador = 1;
        $ultima_data = '1970-01-01 00:00:00'; // Inicializa a data limite

        foreach ($sessoes_do_projeto as $h) {
            $d = new DateTime($h['data_hora']);
            $ultima_data = $h['data_hora']; // Salva a data da última sessão rodada no loop

            if ($h['status'] == 'Concluído') {
                $historico_montado[] = ['desc' => "{$contador}ª Sessão: Concluída em " . $d->format('d/m/Y'), 'icone' => 'bi-check-circle-fill text-success'];
                $contador++;
            } elseif ($h['status'] == 'Cancelado') {
                $motivo = htmlspecialchars($h['motivo_cancelamento'] ?? 'Desistência/Imprevisto');
                $historico_montado[] = ['desc' => "Sessão Cancelada em " . $d->format('d/m/Y') . " | Motivo: {$motivo}", 'icone' => 'bi-x-circle-fill text-danger'];
            }
        }

        $historico[] = [
            'tipo' => 'concluido',
            'titulo' => htmlspecialchars($row['titulo']),
            'status' => $row['status'],
            'status_class' => ($row['status'] == 'Finalizado') ? 'status-concluido' : 'status-cancelado',
            'local' => htmlspecialchars($row['local_corpo'] ?? 'Não informado'),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado'] ?? 'Não informado'),
            'ideia' => htmlspecialchars($row['descricao_ideia'] ?? ''),
            'ref' => 'Sem referência',
            'sessoes_estimadas' => htmlspecialchars($row['qtd_sessoes'] ?? '-'),
            'historico_sessoes' => $historico_montado,
            'data_sort' => $ultima_data // Guarda a última data deste projeto
        ];
    }

    // MAGIA DE ORDENAÇÃO: Pega o array completo e ordena puxando as datas mais recentes para cima
    usort($historico, function ($a, $b) {
        return strtotime($b['data_sort']) - strtotime($a['data_sort']);
    });
} catch (PDOException $e) {
}
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
    } else {
        echo '<a href="' . $link_prefix . 'dashboard-cliente.php" class="' . ($pagina_ativa == 'dashboard-cliente.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="' . $link_prefix . 'agendamentos-cliente.php" class="' . ($pagina_ativa == 'agendamentos-cliente.php' ? 'active' : '') . '">Meus Agendamentos</a>';
        echo '<a href="' . $link_prefix . 'solicitar-orcamento.php" class="' . ($pagina_ativa == 'solicitar-orcamento.php' ? 'active' : '') . '">Orçamento</a>';
        echo '<a href="' . $link_prefix . 'configuracoes-cliente.php" class="' . ($pagina_ativa == 'configuracoes-cliente.php' ? 'active' : '') . '">Configurações</a>';
    }
    echo '</div>';
}
?>

<main>
    <div class="container my-5 py-5">
        <h2 class="text-center mb-5">MEUS AGENDAMENTOS</h2>

        <?php if (isset($_GET['cancelado']) && $_GET['cancelado'] == 'orcamento'): ?>
            <div class="alert alert-success text-center mb-4 alert-dismissible fade show" role="alert">
                Sua solicitação de orçamento foi removida com sucesso.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h4 class="mb-4">Ação Requerida</h4>

        <?php if (empty($projetos_para_agendar)): ?>
            <div class="card-resumo text-center text-white-50 mb-5">
                Você não tem nenhuma ação pendente no momento.
            </div>
        <?php else: ?>
            <div class="accordion mb-5" id="acordeaoAcaoRequerida">
                <?php foreach ($projetos_para_agendar as $i => $proj): ?>
                    <div class="accordion-item card-acao mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-acao-<?php echo $i; ?>">
                                <div class="w-100 d-flex justify-content-between align-items-center">
                                    <span><strong>Projeto:</strong> <?php echo $proj['titulo']; ?></span>
                                    <span class="badge <?php echo $proj['status_class']; ?> me-3"><?php echo $proj['status']; ?></span>
                                </div>
                            </button>
                        </h2>
                        <div id="item-acao-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoAcaoRequerida">
                            <div class="accordion-body">
                                <?php if (!empty($proj['motivo_reagendamento'])): ?>
                                    <div class="alert alert-warning p-2 small mt-2">
                                        <strong>⚠️</strong> Escolha uma nova data | Motivo informado: <em>"<?php echo htmlspecialchars($proj['motivo_reagendamento']); ?>"</em>
                                    </div>
                                <?php endif; ?>

                                <p class="text-white-50 mb-2 mt-3"><strong>Detalhes do Projeto:</strong></p>
                                <div class="small mb-3">
                                    <p class="mb-1"><strong>Local do Corpo:</strong> <?php echo $proj['local']; ?></p>
                                    <p class="mb-1"><strong>Tamanho Aproximado:</strong> <?php echo $proj['tamanho_desc']; ?></p>
                                    <p class="mb-1"><strong>Sua Ideia:</strong> <?php echo $proj['ideia']; ?></p>
                                    <p class="mb-1"><strong>Referência Enviada:</strong>
                                        <?php if ($proj['ref'] !== 'Sem referência' && $proj['ref'] !== ''): ?>
                                            <a href="../imagens/orcamentos/<?php echo $proj['ref']; ?>" target="_blank" class="text-info text-decoration-none">
                                                <i class="bi bi-image me-1"></i> Ver Anexo
                                            </a>
                                        <?php else: ?>
                                            <span class="text-white-50">Vazio</span>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <hr class="my-4" style="border-color: #444;">

                                <p class="text-white-50 mb-2"><strong>Detalhes da Sessão:</strong></p>
                                <div class="small mb-3">
                                    <p class="mb-1"><strong>Duração da Sessão:</strong> <?php echo $proj['duracao']; ?></p>
                                    <p class="mb-0"><strong>Total de Sessões:</strong> <?php echo $proj['sessoes_estimadas']; ?></p>
                                </div>

                                <?php if (!empty($proj['historico_sessoes'])): ?>
                                    <hr class="my-4" style="border-color: #444;">

                                    <p class="text-white-50 mb-2"><strong>Histórico de Sessões:</strong></p>
                                    <div class="p-3" style="background-color: #2c2c2c; border-radius: 8px;">
                                        <?php foreach ($proj['historico_sessoes'] as $hist): ?>
                                            <div class="d-flex justify-content-between align-items-center small p-2 border-bottom border-dark">
                                                <span class="text-white-50">
                                                    <i class="bi <?php echo $hist['icone']; ?> me-2"></i> <?php echo $hist['desc']; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="text-end mt-4">
                                    <?php
                                    $link_agendar = "agenda.php?";
                                    if (isset($proj['id_projeto'])) $link_agendar .= "projeto_id=" . $proj['id_projeto'];
                                    else $link_agendar .= "orcamento_id=" . $proj['id'];
                                    ?>
                                    <a href="<?php echo $link_agendar; ?>" class="btn btn-secondary ">AGENDAR SESSÃO</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h4 class="mb-4">Meus Projetos</h4>

        <ul class="nav nav-tabs nav-tabs-dark mb-4" id="abasProjetos" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="proximas-tab" data-bs-toggle="tab" data-bs-target="#tab-proximas" type="button" role="tab" aria-controls="tab-proximas" aria-selected="true">Próximas Sessões</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analise-tab" data-bs-toggle="tab" data-bs-target="#tab-analise" type="button" role="tab" aria-controls="tab-analise" aria-selected="false">Em Análise</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button" role="tab" aria-controls="tab-historico" aria-selected="false">Histórico</button>
            </li>
        </ul>

        <div class="tab-content tab-content-boxed" id="abasProjetosConteudo">

            <div class="tab-pane fade show active" id="tab-proximas" role="tabpanel" aria-labelledby="proximas-tab">
                <?php if (empty($proximas_sessoes)): ?>
                    <div class="card-resumo text-center text-white-50">
                        Você não possui nenhuma sessão agendada.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoProximasSessoes">
                        <?php foreach ($proximas_sessoes as $i => $sessao): ?>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sessao-<?php echo $i; ?>">
                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                            <span><strong>Projeto:</strong> <?php echo $sessao['titulo']; ?></span>
                                            <span class="me-3"><strong>Próxima Sessão:</strong> <?php echo $sessao['data']; ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="sessao-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoProximasSessoes">
                                    <div class="accordion-body">
                                        <p class="text-white-50 mb-2 mt-3"><strong>Detalhes do Projeto:</strong></p>
                                        <div class="small mb-3">
                                            <p class="mb-1"><strong>Local do Corpo:</strong> <?php echo $sessao['local']; ?></p>
                                            <p class="mb-1"><strong>Tamanho Aproximado:</strong> <?php echo $sessao['tamanho_desc']; ?></p>
                                            <p class="mb-1"><strong>Sua Ideia:</strong> <?php echo $sessao['ideia']; ?></p>
                                            <p class="mb-1"><strong>Referência Enviada:</strong>
                                                <?php if ($sessao['ref'] !== 'Sem referência' && $sessao['ref'] !== ''): ?>
                                                    <a href="../imagens/orcamentos/<?php echo $sessao['ref']; ?>" target="_blank" class="text-info text-decoration-none">
                                                        <i class="bi bi-image me-1"></i> Ver Anexo
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-white-50">Vazio</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>

                                        <hr class="my-4" style="border-color: #444;">

                                        <p class="text-white-50 mb-2"><strong>Detalhes da Sessão:</strong></p>
                                        <div class="small mb-3">
                                            <p class="mb-1"><strong>Duração da Sessão:</strong> <?php echo $sessao['duracao']; ?></p>
                                            <p class="mb-0"><strong>Total de Sessões:</strong> <?php echo $sessao['sessoes_estimadas']; ?></p>
                                        </div>

                                        <hr class="my-4" style="border-color: #444;">

                                        <p class="text-white-50 mb-2"><strong>Histórico de Sessões:</strong></p>
                                        <div class="p-3" style="background-color: #2c2c2c; border-radius: 8px;">
                                            <?php foreach ($sessao['historico_sessoes'] as $hist): ?>
                                                <div class="d-flex justify-content-between align-items-center small p-2 border-bottom border-dark">
                                                    <span class="<?php echo strpos($hist['icone'], 'text-success') !== false ? 'text-white-50' : 'text-light'; ?>">
                                                        <i class="bi <?php echo $hist['icone']; ?> me-2"></i> <?php echo $hist['desc']; ?>
                                                    </span>
                                                    <?php if ($hist['pode_cancelar']): ?>
                                                        <div class="d-flex gap-2">
                                                            <button class="btn btn-sm btn-outline-warning btn-reagendar" data-id="<?php echo $sessao['id']; ?>" data-bs-toggle="modal" data-bs-target="#modalReagendar">Reagendar</button>
                                                            <button class="btn btn-sm btn-outline-danger btn-cancelar-projeto" data-id="<?php echo $sessao['id']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelarProjeto">Cancelar Projeto</button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tab-analise" role="tabpanel" aria-labelledby="analise-tab">
                <?php if (empty($orcamentos_pendentes)): ?>
                    <div class="card-resumo text-center text-white-50">
                        Você não possui nenhum orçamento em análise.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="acordeaoPendentes">
                        <?php foreach ($orcamentos_pendentes as $i => $proj): ?>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-analise-<?php echo $i; ?>">
                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                            <span><strong>Projeto:</strong> <?php echo $proj['titulo']; ?></span>
                                            <span class="badge <?php echo $proj['status_class']; ?> me-3"><?php echo $proj['status']; ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="item-analise-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoPendentes">
                                    <div class="accordion-body">
                                        <p class="text-white-50 mb-2 mt-3"><strong>Detalhes da Solicitação:</strong></p>
                                        <div class="small mb-3">
                                            <p class="mb-1"><strong>Local do Corpo:</strong> <?php echo $proj['local']; ?></p>
                                            <p class="mb-1"><strong>Tamanho Aproximado:</strong> <?php echo $proj['tamanho_desc']; ?></p>
                                            <p class="mb-1"><strong>Sua Ideia:</strong> <?php echo $proj['ideia']; ?></p>
                                            <p class="mb-0"><strong>Referência Enviada:</strong>
                                                <?php if ($proj['ref'] !== 'Sem referência' && $proj['ref'] !== ''): ?>
                                                    <a href="../imagens/orcamentos/<?php echo $proj['ref']; ?>" target="_blank" class="text-info text-decoration-none">
                                                        <i class="bi bi-image me-1"></i> Ver Anexo
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-white-50">Vazio</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>

                                        <hr class="my-4" style="border-color: #444;">

                                        <p class="mb-0"><?php echo $proj['detalhe_status']; ?></p>

                                        <div class="text-end mt-3">
                                            <button class="btn btn-sm btn-outline-danger btn-cancelar-orc" data-id="<?php echo $proj['id']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelarOrcamento">Cancelar Solicitação</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tab-historico" role="tabpanel" aria-labelledby="historico-tab">
                <?php if (empty($historico)): ?>
                    <div class="card-resumo text-center text-white-50">
                        Seu histórico está vazio.
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-end mb-3">
                        <select id="filtroStatusHistorico" class="form-select form-select-sm bg-dark text-light border-secondary w-auto shadow-none">
                            <option value="todos">Ver Tudo</option>
                            <option value="Finalizado">Finalizados</option>
                            <option value="Cancelado">Cancelados</option>
                            <option value="Recusado">Recusados</option>
                        </select>
                    </div>

                    <div class="accordion" id="acordeaoHistorico">
                        <?php foreach ($historico as $i => $item): ?>
                            <div class="accordion-item mb-3 historico-item-js" data-status="<?php echo $item['status']; ?>">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#item-hist-<?php echo $i; ?>">
                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                            <span><strong>Projeto:</strong> <?php echo $item['titulo']; ?></span>
                                            <span class="badge <?php echo $item['status_class']; ?> me-3"><?php echo $item['status']; ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="item-hist-<?php echo $i; ?>" class="accordion-collapse collapse" data-bs-parent="#acordeaoHistorico">
                                    <div class="accordion-body">
                                        <p class="text-white-50 mb-2 mt-3"><strong>Detalhes do Projeto:</strong></p>
                                        <div class="small mb-3">
                                            <p class="mb-1"><strong>Local do Corpo:</strong> <?php echo $item['local']; ?></p>
                                            <p class="mb-1"><strong>Tamanho Aproximado:</strong> <?php echo $item['tamanho_desc']; ?></p>
                                            <p class="mb-1"><strong>Sua Ideia:</strong> <?php echo $item['ideia']; ?></p>
                                            <p class="mb-0"><strong>Referência Enviada:</strong>
                                                <?php if ($item['ref'] !== 'Sem referência' && $item['ref'] !== ''): ?>
                                                    <a href="../imagens/orcamentos/<?php echo $item['ref']; ?>" target="_blank" class="text-info text-decoration-none">
                                                        <i class="bi bi-image me-1"></i> Ver Anexo
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-white-50">Vazio</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>

                                        <hr class="my-4" style="border-color: #444;">

                                        <?php if ($item['tipo'] == 'recusado'): ?>
                                            <p class="text-white-50 mb-2"><strong>Status da Solicitação:</strong></p>
                                            <div class="p-3" style="background-color: #2c2c2c; border-radius: 8px;">
                                                <div class="d-flex justify-content-between align-items-center small p-2 border-bottom border-dark">
                                                    <span class="text-warning">
                                                        <i class="bi bi-x-circle-fill text-danger me-2"></i> Solicitação Recusada | Motivo: <?php echo $item['detalhe_status']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-white-50 mb-2"><strong>Histórico de Sessões:</strong></p>
                                            <div class="p-3" style="background-color: #2c2c2c; border-radius: 8px;">
                                                <?php if (empty($item['historico_sessoes'])): ?>
                                                    <div class="small text-white-50 p-2">Nenhuma sessão registrada.</div>
                                                <?php else: ?>
                                                    <?php foreach ($item['historico_sessoes'] as $hist): ?>
                                                        <div class="d-flex justify-content-between align-items-center small p-2 border-bottom border-dark">
                                                            <span class="<?php echo strpos($hist['icone'], 'text-danger') !== false ? 'text-warning' : 'text-white-50'; ?>">
                                                                <i class="bi <?php echo $hist['icone']; ?> me-2"></i> <?php echo $hist['desc']; ?>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
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

<div class="modal fade" id="modalReagendar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Reagendar Sessão</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>A data atual será desmarcada e você será levado para o calendário para escolher um novo dia.</p>
                <form action="../actions/a.reagendar.php" method="POST">
                    <input type="hidden" name="sessao_id" id="inputReagendarId" value="">
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
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-danger">Cancelar Projeto</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Atenção: Isso cancelará não apenas esta sessão, mas o <strong>projeto inteiro</strong>.</p>
                <form action="../actions/a.cancelar-projeto.php" method="POST">
                    <input type="hidden" name="sessao_id" id="inputCancelarProjetoId" value="">
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

<div class="modal fade" id="modalCancelarOrcamento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title">Cancelar Solicitação</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Você tem certeza que deseja cancelar esta solicitação de orçamento?</p>
                <p class="small">O artista ainda não analisou este pedido. Ao cancelar, ele será removido da fila.</p>
                <form action="../actions/a.cancelar-orcamento.php" method="POST">
                    <input type="hidden" name="orcamento_id" id="inputOrcamentoId" value="">
                    <div class="modal-footer border-top border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const tabButtons = document.querySelectorAll('#abasProjetos .nav-link');
        const accordionCollapses = document.querySelectorAll('.accordion-collapse');
        const collapseInstances = Array.from(accordionCollapses).map(collapseEl => {
            return new bootstrap.Collapse(collapseEl, {
                toggle: false
            });
        });

        // O filtro estava num lugar errado, agora está blindado aqui:
        const filtroHistorico = document.getElementById('filtroStatusHistorico');
        if (filtroHistorico) {
            filtroHistorico.addEventListener('change', function() {
                const filtro = this.value;
                const itensHistorico = document.querySelectorAll('.historico-item-js');

                itensHistorico.forEach(item => {
                    if (filtro === 'todos' || item.getAttribute('data-status') === filtro) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const collapsesInsideTabs = document.querySelectorAll('#abasProjetosConteudo .accordion-collapse');
                collapsesInsideTabs.forEach(collapseEl => {
                    const instance = bootstrap.Collapse.getInstance(collapseEl);
                    if (instance) instance.hide();
                });
            });
        });

        const acoesCollapse = document.querySelectorAll('#acordeaoAcaoRequerida .accordion-collapse');
        acoesCollapse.forEach(collapseEl => {
            collapseEl.addEventListener('show.bs.collapse', () => {
                acoesCollapse.forEach(otherCollapseEl => {
                    if (otherCollapseEl !== collapseEl) {
                        const instance = bootstrap.Collapse.getInstance(otherCollapseEl);
                        if (instance) instance.hide();
                    }
                });
            });
        });

        const btnsReagendar = document.querySelectorAll('.btn-reagendar');
        const inputReagendarId = document.getElementById('inputReagendarId');
        btnsReagendar.forEach(btn => {
            btn.addEventListener('click', function() {
                inputReagendarId.value = this.getAttribute('data-id');
            });
        });

        const btnsCancelarProj = document.querySelectorAll('.btn-cancelar-projeto');
        const inputCancelarProjetoId = document.getElementById('inputCancelarProjetoId');
        btnsCancelarProj.forEach(btn => {
            btn.addEventListener('click', function() {
                inputCancelarProjetoId.value = this.getAttribute('data-id');
            });
        });

        const btnsCancelarOrc = document.querySelectorAll('.btn-cancelar-orc');
        const inputOrcamentoId = document.getElementById('inputOrcamentoId');
        btnsCancelarOrc.forEach(btn => {
            btn.addEventListener('click', function() {
                inputOrcamentoId.value = this.getAttribute('data-id');
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>