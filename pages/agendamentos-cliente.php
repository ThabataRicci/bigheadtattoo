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

// 1. BUSCAR ORÇAMENTOS PENDENTES (Em Análise ou em Negociação com o Artista)
try {
    $sql_pendentes = "SELECT * FROM orcamento WHERE id_usuario = ? AND (status IN ('Pendente', 'Negociacao') OR status IS NULL)";
    $stmt = $pdo->prepare($sql_pendentes);
    $stmt->execute([$id_usuario]);

    foreach ($stmt->fetchAll() as $row) {
        $ideia_completa = htmlspecialchars($row['descricao_ideia']);
        $status_amigavel = ($row['status'] == 'Negociacao') ? 'Em Negociação' : 'Aguardando Análise';

        $orcamentos_pendentes[] = [
            'id' => $row['id_orcamento'],
            'titulo' => mb_strimwidth($ideia_completa, 0, 30, "..."),
            'status' => $status_amigavel,
            'status_class' => 'status-analise',
            'local' => htmlspecialchars($row['local_corpo']),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
            'ideia' => '"' . $ideia_completa . '"',
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'valor' => !empty($row['valor_sessao']) ? number_format($row['valor_sessao'], 2, ',', '.') : 'Não definido',
            'valor_anterior' => !empty($row['valor_sessao_anterior']) ? number_format($row['valor_sessao_anterior'], 2, ',', '.') : '',
            'detalhe_status' => 'Sua ideia está com o artista. Aguarde o retorno com a proposta de valor.'
        ];
    }
} catch (PDOException $e) {
}

// 2. BUSCAR PRÓXIMAS SESSÕES AGENDADAS
try {
    $sql_sessoes = "SELECT s.id_sessao, s.data_hora, p.titulo, p.id_projeto, o.local_corpo, o.tamanho_aproximado, o.descricao_ideia, o.estimativa_tempo, o.referencia_ideia, o.qtd_sessoes, o.valor_sessao,
                           (SELECT COUNT(*) FROM sessao s2 WHERE s2.id_projeto = p.id_projeto AND s2.status = 'Concluído') AS sessoes_realizadas
                    FROM sessao s 
                    JOIN projeto p ON s.id_projeto = p.id_projeto
                    LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento
                    WHERE p.id_usuario = ? AND s.status = 'Agendado' AND s.data_hora >= NOW() 
                    ORDER BY s.data_hora ASC";
    $stmt = $pdo->prepare($sql_sessoes);
    $stmt->execute([$id_usuario]);
    foreach ($stmt->fetchAll() as $row) {
        $data_obj = new DateTime($row['data_hora']);

        $stmt_hist = $pdo->prepare("SELECT id_sessao, data_hora, status, motivo_cancelamento, valor_sessao FROM sessao WHERE id_projeto = ? ORDER BY data_hora ASC");
        $stmt_hist->execute([$row['id_projeto']]);

        $historico_montado = [];
        $contador = 1;
        foreach ($stmt_hist->fetchAll() as $h) {
            $d = new DateTime($h['data_hora']);
            if ($h['status'] == 'Concluído') {
                $val = !empty($h['valor_sessao']) ? " | R$ " . number_format($h['valor_sessao'], 2, ',', '.') : "";
                $historico_montado[] = ['desc' => "{$contador}ª Sessão: Concluída em " . $d->format('d/m/Y') . $val, 'icone' => 'bi-check-circle-fill text-success', 'pode_cancelar' => false];
                $contador++;
            } elseif ($h['status'] == 'Cancelado') {
                $motivo = htmlspecialchars($h['motivo_cancelamento'] ?? 'Reagendado/Imprevisto');
                $historico_montado[] = ['desc' => "Sessão Cancelada em " . $d->format('d/m/Y') . " | {$motivo}", 'icone' => 'bi-x-circle-fill text-danger', 'pode_cancelar' => false];
            } elseif ($h['status'] == 'Agendado') {
                $pode = ($h['id_sessao'] == $row['id_sessao']);
                $historico_montado[] = ['desc' => "{$contador}ª Sessão: Agendada para " . $d->format('d/m/Y H:i'), 'icone' => 'bi-calendar-event text-info', 'pode_cancelar' => $pode];
            }
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
            'sessoes_realizadas' => (int)$row['sessoes_realizadas'] + 1,
            'valor' => !empty($row['valor_sessao']) ? number_format($row['valor_sessao'], 2, ',', '.') : 'Não definido',
            'historico_sessoes' => $historico_montado
        ];
    }

    // 3. AÇÃO REQUERIDA: Avaliar Proposta do Artista (Aguardando Aceite)
    $sql_aprovados = "SELECT * FROM orcamento WHERE id_usuario = ? AND status = 'Aguardando Aceite' ORDER BY id_orcamento ASC";
    $stmt = $pdo->prepare($sql_aprovados);
    $stmt->execute([$id_usuario]);
    foreach ($stmt->fetchAll() as $row) {
        $projetos_para_agendar[] = [
            'id_orcamento' => $row['id_orcamento'],
            'titulo' => htmlspecialchars($row['titulo_sugerido'] ?? 'Proposta de Projeto'),
            'status' => 'Avaliar Proposta',
            'status_class' => 'status-acao',
            'local' => htmlspecialchars($row['local_corpo']),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
            'ideia' => htmlspecialchars($row['descricao_ideia']),
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'duracao' => htmlspecialchars($row['estimativa_tempo']),
            'sessoes_estimadas' => htmlspecialchars($row['qtd_sessoes']),
            'valor_sessao' => !empty($row['valor_sessao']) ? number_format($row['valor_sessao'], 2, ',', '.') : '',
            'valor_anterior' => !empty($row['valor_sessao_anterior']) ? number_format($row['valor_sessao_anterior'], 2, ',', '.') : '',
            'tentativas' => $row['tentativas_negociacao'],
            'motivo_reagendamento' => null,
            'tipo_acao' => 'avaliar_orcamento'
        ];
    }

    // 3.B AÇÃO REQUERIDA: Agendar ou Reagendar Sessão (Projeto já existente)
    $sql_reagendar = "SELECT p.*, o.local_corpo, o.tamanho_aproximado, o.descricao_ideia, o.estimativa_tempo, o.qtd_sessoes, o.valor_sessao,
                             (SELECT COUNT(*) FROM sessao s2 WHERE s2.id_projeto = p.id_projeto AND s2.status = 'Concluído') AS sessoes_realizadas
                      FROM projeto p 
                      LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento 
                      WHERE p.id_usuario = ? AND p.status = 'Agendamento Pendente' ORDER BY p.id_projeto ASC";
    $stmt = $pdo->prepare($sql_reagendar);
    $stmt->execute([$id_usuario]);
    foreach ($stmt->fetchAll() as $row) {
        $stmt_hist = $pdo->prepare("SELECT id_sessao, data_hora, status, motivo_cancelamento, valor_sessao FROM sessao WHERE id_projeto = ? ORDER BY data_hora ASC");
        $stmt_hist->execute([$row['id_projeto']]);

        $historico_montado = [];
        $contador = 1;
        foreach ($stmt_hist->fetchAll() as $h) {
            $d = new DateTime($h['data_hora']);
            if ($h['status'] == 'Concluído') {
                $val = !empty($h['valor_sessao']) ? " | R$ " . number_format($h['valor_sessao'], 2, ',', '.') : "";
                $historico_montado[] = ['desc' => "{$contador}ª Sessão: Concluída em " . $d->format('d/m/Y') . $val, 'icone' => 'bi-check-circle-fill text-success'];
                $contador++;
            } elseif ($h['status'] == 'Cancelado') {
                $motivo = htmlspecialchars($h['motivo_cancelamento'] ?? 'Reagendado/Imprevisto');
                $historico_montado[] = ['desc' => "Sessão Cancelada em " . $d->format('d/m/Y') . " | {$motivo}", 'icone' => 'bi-x-circle-fill text-danger'];
            }
        }

        $is_reagendamento = !empty($row['motivo_reagendamento']);

        $projetos_para_agendar[] = [
            'id_projeto' => $row['id_projeto'],
            'titulo' => htmlspecialchars($row['titulo']),
            'status' => $is_reagendamento ? 'Reagendar' : 'Agendar Sessão',
            'status_class' => 'status-acao',
            'local' => htmlspecialchars($row['local_corpo'] ?? 'Não informado'),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado'] ?? 'Não informado'),
            'ideia' => htmlspecialchars($row['descricao_ideia'] ?? 'Escolha a data.'),
            'ref' => 'Sem referência',
            'duracao' => htmlspecialchars($row['estimativa_tempo'] ?? 'A definir'),
            'sessoes_realizadas' => $row['sessoes_realizadas'],
            'sessoes_estimadas' => htmlspecialchars($row['qtd_sessoes'] ?? '-'),
            'valor' => !empty($row['valor_sessao']) ? number_format($row['valor_sessao'], 2, ',', '.') : 'Não definido',
            'motivo_reagendamento' => $row['motivo_reagendamento'],
            'historico_sessoes' => $historico_montado,
            'tipo_acao' => 'agendar_sessao'
        ];
    }
} catch (PDOException $e) {
}

// 4. HISTÓRICO GERAL 
try {
    // Orçamentos Recusados (Pelo Artista) ou Cancelados (Pelo Cliente na negociação)
    $sql_recusados = "SELECT * FROM orcamento WHERE id_usuario = ? AND status IN ('Recusado', 'Cancelado pelo Cliente')";
    $stmt = $pdo->prepare($sql_recusados);
    $stmt->execute([$id_usuario]);
    foreach ($stmt->fetchAll() as $row) {
        $ideia_completa = htmlspecialchars($row['descricao_ideia']);

        $motivo_exibicao = !empty($row['motivo_cancelamento_cliente']) ? htmlspecialchars($row['motivo_cancelamento_cliente']) : htmlspecialchars($row['motivo_recusa'] ?? 'Sem detalhes');

        $historico[] = [
            'tipo' => 'recusado',
            'titulo' => mb_strimwidth($ideia_completa, 0, 30, "..."),
            'status' => $row['status'],
            'status_class' => 'status-cancelado',
            'local' => htmlspecialchars($row['local_corpo']),
            'tamanho_desc' => htmlspecialchars($row['tamanho_aproximado']),
            'ideia' => '"' . $ideia_completa . '"',
            'ref' => $row['referencia_ideia'] ? $row['referencia_ideia'] : 'Sem referência',
            'sessoes_realizadas' => 0,
            'valor' => !empty($row['valor_sessao']) ? number_format($row['valor_sessao'], 2, ',', '.') : 'Não definido',
            'detalhe_status' => $motivo_exibicao,
            'data_sort' => $row['data_envio'] ?? '1970-01-01 00:00:00'
        ];
    }

    // Projetos Finalizados ou Cancelados definitivamente
    $sql_hist_proj = "SELECT p.*, o.local_corpo, o.tamanho_aproximado, o.descricao_ideia, o.qtd_sessoes, o.valor_sessao 
                      FROM projeto p 
                      LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento
                      WHERE p.id_usuario = ? AND p.status IN ('Finalizado', 'Cancelado')
                      ORDER BY p.id_projeto DESC";
    $stmt = $pdo->prepare($sql_hist_proj);
    $stmt->execute([$id_usuario]);
    foreach ($stmt->fetchAll() as $row) {
        $stmt_hist = $pdo->prepare("SELECT data_hora, status, motivo_cancelamento, valor_sessao FROM sessao WHERE id_projeto = ? ORDER BY data_hora ASC");
        $stmt_hist->execute([$row['id_projeto']]);

        $historico_montado = [];
        $contador = 1;
        $ultima_data = '1970-01-01 00:00:00';

        foreach ($stmt_hist->fetchAll() as $h) {
            $d = new DateTime($h['data_hora']);
            $ultima_data = $h['data_hora'];

            if ($h['status'] == 'Concluído') {
                $val = !empty($h['valor_sessao']) ? " | R$ " . number_format($h['valor_sessao'], 2, ',', '.') : "";
                $historico_montado[] = ['desc' => "{$contador}ª Sessão: Concluída em " . $d->format('d/m/Y') . $val, 'icone' => 'bi-check-circle-fill text-success'];
                $contador++;
            } elseif ($h['status'] == 'Cancelado') {
                $motivo = htmlspecialchars($h['motivo_cancelamento'] ?? 'Desistência/Imprevisto');
                $historico_montado[] = ['desc' => "Sessão Cancelada em " . $d->format('d/m/Y') . " | {$motivo}", 'icone' => 'bi-x-circle-fill text-danger'];
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
            'sessoes_realizadas' => $contador - 1,
            'valor' => !empty($row['valor_sessao']) ? number_format($row['valor_sessao'], 2, ',', '.') : 'Não definido',
            'historico_sessoes' => $historico_montado,
            'data_sort' => $ultima_data
        ];
    }

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
        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'aceito'): ?>
            <div class="alert alert-success text-center mb-4 alert-dismissible fade show" role="alert">
                Proposta aceita! O seu projeto foi criado. Escolha uma data para a 1ª sessão.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'recusado'): ?>
            <div class="alert alert-warning text-center mb-4 alert-dismissible fade show" role="alert">
                Sua resposta foi enviada ao artista.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'cancelado'): ?>
            <div class="alert alert-success text-center mb-4 alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i> O projeto foi cancelado com sucesso.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <style>
            .card-hover {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                border: 1px solid #444;
                border-radius: 8px;
                padding: 20px;
                background-color: #212529;
            }

            .card-hover:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 15px rgba(13, 202, 240, 0.2);
                border-color: #0dcaf0;
                cursor: pointer;
            }

            .card-resumo-numero {
                font-size: 2.5rem;
                font-weight: bold;
                color: #fff;
                margin-bottom: 5px;
            }
        </style>



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
                                        <strong>⚠️</strong> Escolha uma nova data | <em><?php echo htmlspecialchars($proj['motivo_reagendamento']); ?></em>
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

                                <?php if (isset($proj['tipo_acao']) && $proj['tipo_acao'] == 'avaliar_orcamento'): ?>
                                    <p class="text-white-50 mb-2"><strong>Proposta do Artista:</strong></p>
                                    <div class="small">

                                        <?php if ($proj['tentativas'] > 0 && !empty($proj['valor_anterior'])): ?>
                                            <p class="mb-1 text-light">
                                                <strong>Novo Valor:</strong>
                                                <span class="text-success"><strong>R$ <?php echo $proj['valor_sessao']; ?></strong></span>
                                                |
                                                <span class="small">
                                                    <strong class="text-white-50">Valor Anterior:</strong>
                                                    <span class="text-danger"><strong>R$ <?php echo $proj['valor_anterior']; ?></strong></span>
                                                </span>
                                            </p>
                                        <?php else: ?>
                                            <p class="mb-1">
                                                <strong>Valor por Sessão:</strong>
                                                R$ <?php echo $proj['valor_sessao']; ?>
                                            </p>
                                        <?php endif; ?>

                                        <p class="mb-1"><strong>Duração Estimada:</strong> <?php echo $proj['duracao']; ?></p>
                                        <p class="mb-0"><strong>Total de Sessões:</strong> <?php echo $proj['sessoes_estimadas']; ?></p>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button class="btn btn-outline-danger me-2 btn-recusar-proposta" data-id="<?php echo $proj['id_orcamento']; ?>" data-tentativas="<?php echo $proj['tentativas']; ?>" data-bs-toggle="modal" data-bs-target="#modalRecusarProposta">Recusar Proposta</button>
                                        <button class="btn btn-success btn-aceitar-proposta" data-id="<?php echo $proj['id_orcamento']; ?>" data-bs-toggle="modal" data-bs-target="#modalAceitarProposta">Aceitar Proposta</button>
                                    </div>

                                <?php else: ?>
                                    <p class="text-white-50 mb-2"><strong>Detalhes da Sessão:</strong></p>
                                    <div class="small mb-3">
                                        <p class="mb-1"><strong>Duração da Sessão:</strong> <?php echo $proj['duracao']; ?></p>
                                        <p class="mb-1"><strong>Sessões Realizadas:</strong> <?php echo ($proj['sessoes_realizadas'] ?? 0); ?> | Estimado: <?php echo $proj['sessoes_estimadas']; ?></p>
                                        <p class="mb-1"><strong>Valor da Sessão:</strong> R$ <?php echo $proj['valor']; ?></p>
                                    </div>

                                    <hr class="my-4" style="border-color: #444;">

                                    <p class="text-white-50 mb-2"><strong>Histórico de Sessões:</strong></p>
                                    <div class="p-3" style="background-color: #2c2c2c; border-radius: 8px;">
                                        <?php if (empty($proj['historico_sessoes'])): ?>
                                            <div class="small text-white-50 p-2">Nenhuma sessão registrada.</div>
                                        <?php else: ?>
                                            <?php foreach ($proj['historico_sessoes'] as $hist): ?>
                                                <div class="d-flex justify-content-between align-items-center small p-2 border-bottom border-dark">
                                                    <span class="<?php echo strpos($hist['icone'], 'text-danger') !== false ? 'text-warning' : 'text-white-50'; ?>">
                                                        <i class="bi <?php echo $hist['icone']; ?> me-2"></i> <?php echo $hist['desc']; ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex justify-content-end align-items-center mt-4 gap-2">
                                        <button type="button" class="btn btn-outline-danger btn-cancelar-projeto-pendente" data-id="<?php echo $proj['id_projeto']; ?>" data-bs-toggle="modal" data-bs-target="#modalCancelarProjetoPendente">Cancelar Projeto</button>

                                        <?php $link_agendar = "agendar-sessao-cliente.php?projeto_id=" . $proj['id_projeto']; ?>
                                        <a href="<?php echo $link_agendar; ?>" class="btn btn meu-botao">Agendar Sessão</a>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <style>
                                .meu-botao {
                                    background-color: #b7b7b7;
                                    color: #000;
                                    border: none;
                                }
                            </style>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h4 class="mb-4">Meus Projetos</h4>

        <ul class="nav nav-tabs nav-tabs-dark mb-4" id="abasProjetos" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="proximas-tab" data-bs-toggle="tab" data-bs-target="#tab-proximas" type="button" role="tab">Próximas Sessões</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analise-tab" data-bs-toggle="tab" data-bs-target="#tab-analise" type="button" role="tab">Em Análise</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button" role="tab">Histórico</button>
            </li>
        </ul>

        <div class="tab-content tab-content-boxed" id="abasProjetosConteudo">

            <div class="tab-pane fade show active" id="tab-proximas" role="tabpanel">
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

                                        <p class="text-white-50 mb-2 mt-3"><strong>Detalhes da Sessão:</strong></p>
                                        <div class="small mb-3">
                                            <p class="mb-1"><strong>Duração da Sessão:</strong> <?php echo $sessao['duracao']; ?></p>
                                            <p class="mb-1"><strong>Sessões Realizadas:</strong> <?php echo $sessao['sessoes_realizadas']; ?> | Estimado: <?php echo $sessao['sessoes_estimadas']; ?></p>
                                            <p class="mb-1"><strong>Valor da Sessão:</strong> R$ <?php echo $sessao['valor']; ?></p>
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

            <div class="tab-pane fade" id="tab-analise" role="tabpanel">
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
                                            <?php if ($proj['valor'] !== 'Não definido' && $proj['valor'] !== ''): ?>
                                                <p class="mb-1 text-warning"><strong>Valor Proposto:</strong> R$ <?php echo $proj['valor']; ?></p>
                                            <?php endif; ?>
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

            <div class="tab-pane fade" id="tab-historico" role="tabpanel">
                <?php if (empty($historico)): ?>
                    <div class="card-resumo text-center text-white-50">
                        Seu histórico está vazio.
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-end mb-3 gap-2">
                        <select id="ordenarHistorico" class="form-select form-select-sm bg-dark text-light border-secondary w-auto shadow-none">
                            <option value="recentes">Mais Recentes</option>
                            <option value="antigos">Mais Antigos</option>
                        </select>
                        <select id="filtroStatusHistorico" class="form-select form-select-sm bg-dark text-light border-secondary w-auto shadow-none">
                            <option value="todos">Ver Tudo</option>
                            <option value="Finalizado">Finalizados</option>
                            <option value="Cancelado">Cancelados</option>
                            <option value="Recusado">Recusados</option>
                        </select>
                    </div>

                    <div class="accordion" id="acordeaoHistorico">
                        <?php foreach ($historico as $i => $item): ?>
                            <div class="accordion-item mb-3 historico-item-js" data-status="<?php echo strpos($item['status'], 'Cancelado') !== false ? 'Cancelado' : $item['status']; ?>" data-sort="<?php echo strtotime($item['data_sort']); ?>">
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
                                            <?php if (isset($item['sessoes_estimadas']) && $item['sessoes_estimadas'] !== '-'): ?>
                                                <p class="mb-1"><strong>Sessões Realizadas:</strong> <?php echo $item['sessoes_realizadas'] ?? 0; ?> | Estimado: <?php echo $item['sessoes_estimadas']; ?></p>
                                            <?php endif; ?>
                                            <?php if ($item['valor'] !== 'Não definido' && $item['valor'] !== ''): ?>
                                                <p class="mb-1"><strong>Valor da Sessão:</strong> R$ <?php echo $item['valor']; ?></p>
                                            <?php endif; ?>
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
                                                        <i class="bi bi-x-circle-fill text-danger me-2"></i> Solicitação Encerrada | Motivo: <?php echo $item['detalhe_status']; ?>
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

<div class="modal fade" id="modalCancelarProjetoPendente" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark border-secondary">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-danger">Cancelar Projeto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Essa ação cancelará o projeto, se desejar realizar a tatuagem precisará enviar o orçamento novamente.</p>
                <form action="../actions/a.cancelar-projeto.php" method="POST">
                    <input type="hidden" name="projeto_id" id="inputCancelarProjetoPendenteId" value="">

                    <div class="mb-3">
                        <label class="form-label text-light">Motivo:</label>
                        <textarea class="form-control bg-dark text-light border-secondary" name="motivo" rows="2" placeholder="Ex: Tive um imprevisto financeiro e não poderei tatuar agora..." required></textarea>
                    </div>
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Cancelar Projeto</button>
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

<div class="modal fade" id="modalAceitarProposta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-success">Aceitar Proposta</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Ao aceitar, o projeto será criado e você poderá agendar a sua 1ª sessão.</p>
                <form action="../actions/a.aceitar-orcamento.php" method="POST">
                    <input type="hidden" name="orcamento_id" id="inputAceitarOrcId" value="">
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-success">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRecusarProposta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-danger">Recusar Proposta</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <form action="../actions/a.recusar-orcamento-cliente.php" method="POST">
                    <input type="hidden" name="orcamento_id" id="inputRecusarOrcIdCli" value="">

                    <p class="mb-3">Por que você deseja recusar esta proposta?</p>

                    <div class="form-check mb-2 d-flex align-items-center" id="radioRecusarPrecoDiv">
                        <input class="form-check-input mt-0 me-2" type="radio" name="tipo_recusa" id="radioRecusarPreco" value="preco" checked>
                        <label class="form-check-label text-light mb-0" for="radioRecusarPreco">
                            Achei o valor alto
                            <i class="bi bi-info-circle ms-2" style="cursor: help;" data-bs-toggle="tooltip" data-bs-placement="top" title="O orçamento voltará ao artista para ele decidir se quer dar um novo valor ou manter o mesmo. Isso só pode ser feito 1 vez."></i>
                        </label>
                    </div>
                    <div id="avisoTentativas" class="small text-danger mb-3" style="display:none; margin-left: 24px;">
                        Você já negociou este orçamento uma vez. Não é possível negociar novamente.
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="tipo_recusa" id="radioRecusarOutro" value="outro">
                        <label class="form-check-label text-light" for="radioRecusarOutro">
                            Outro motivo
                            <i class="bi bi-info-circle ms-2 icone-info"
                                style="cursor: help;"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Essa ação encerrará o orçamento desse projeto"></i>
                        </label>
                    </div>

                    <div id="divMotivoOutro" class="mb-3" style="display:none;">
                        <textarea class="form-control bg-dark text-light border-secondary" id="motivo_cancelamento_cliente" name="motivo_cancelamento_cliente" rows="2" placeholder=""></textarea>
                    </div>

                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Recusar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- LÓGICA PARA ABRIR ABA DIRETO PELA URL ---
        const urlParams = new URLSearchParams(window.location.search);
        const abaAtiva = urlParams.get('aba');

        if (abaAtiva) {
            // Se for aba "proximas" ou "analise", clica no botão da tab
            if (abaAtiva === 'proximas') {
                const btn = document.getElementById('proximas-tab');
                if (btn) new bootstrap.Tab(btn).show();
            } else if (abaAtiva === 'analise') {
                const btn = document.getElementById('analise-tab');
                if (btn) new bootstrap.Tab(btn).show();
            }
        }

        // Ativar os balõezinhos de informação
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Tabs & Accordions
        const tabButtons = document.querySelectorAll('#abasProjetos .nav-link');
        const accordionCollapses = document.querySelectorAll('.accordion-collapse');
        const collapseInstances = Array.from(accordionCollapses).map(collapseEl => {
            return new bootstrap.Collapse(collapseEl, {
                toggle: false
            });
        });

        // Filtro Histórico
        const filtroHistorico = document.getElementById('filtroStatusHistorico');
        if (filtroHistorico) {
            filtroHistorico.addEventListener('change', function() {
                const filtro = this.value;
                const itensHistorico = document.querySelectorAll('.historico-item-js');

                itensHistorico.forEach(item => {
                    if (filtro === 'todos' || item.getAttribute('data-status').includes(filtro)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }

        const ordenarHistorico = document.getElementById('ordenarHistorico');
        if (ordenarHistorico) {
            ordenarHistorico.addEventListener('change', function() {
                const ordem = this.value;
                const container = document.getElementById('acordeaoHistorico');
                const itens = Array.from(container.querySelectorAll('.historico-item-js'));

                itens.sort((a, b) => {
                    const dataA = parseInt(a.getAttribute('data-sort'));
                    const dataB = parseInt(b.getAttribute('data-sort'));
                    return ordem === 'recentes' ? dataB - dataA : dataA - dataB;
                });

                itens.forEach(item => container.appendChild(item));
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

        // Botões de Ação (Puxar IDs pros Modais)
        const btnsReagendar = document.querySelectorAll('.btn-reagendar');
        const inputReagendarId = document.getElementById('inputReagendarId');
        btnsReagendar.forEach(btn => {
            btn.addEventListener('click', function() {
                inputReagendarId.value = this.getAttribute('data-id');
            });
        });

        // Cancelar projeto pendente (Ação Requerida - antes de ter sessão)
        const btnsCancelarProjPendente = document.querySelectorAll('.btn-cancelar-projeto-pendente');
        const inputCancelarProjetoPendenteId = document.getElementById('inputCancelarProjetoPendenteId');
        btnsCancelarProjPendente.forEach(btn => {
            btn.addEventListener('click', function() {
                inputCancelarProjetoPendenteId.value = this.getAttribute('data-id');
            });
        });

        const btnsCancelarOrc = document.querySelectorAll('.btn-cancelar-orc');
        const inputOrcamentoId = document.getElementById('inputOrcamentoId');
        btnsCancelarOrc.forEach(btn => {
            btn.addEventListener('click', function() {
                inputOrcamentoId.value = this.getAttribute('data-id');
            });
        });

        // avaliar proposta
        const btnsAceitarProp = document.querySelectorAll('.btn-aceitar-proposta');
        const inputAceitarOrcId = document.getElementById('inputAceitarOrcId');
        btnsAceitarProp.forEach(btn => {
            btn.addEventListener('click', function() {
                inputAceitarOrcId.value = this.getAttribute('data-id');
            });
        });

        const radioMotivo = document.querySelectorAll('input[name="tipo_recusa"]');
        const divMotivoOutro = document.getElementById('divMotivoOutro');
        const inputMotivoOutro = document.getElementById('motivo_cancelamento_cliente');

        radioMotivo.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'outro') {
                    divMotivoOutro.style.display = 'block';
                    inputMotivoOutro.required = true;
                } else {
                    divMotivoOutro.style.display = 'none';
                    inputMotivoOutro.required = false;
                }
            });
        });

        const btnsRecusarCli = document.querySelectorAll('.btn-recusar-proposta');
        const inputRecusarOrcIdCli = document.getElementById('inputRecusarOrcIdCli');
        btnsRecusarCli.forEach(btn => {
            btn.addEventListener('click', function() {
                inputRecusarOrcIdCli.value = this.getAttribute('data-id');
                const tentativas = parseInt(this.getAttribute('data-tentativas'));

                if (tentativas >= 1) {
                    document.getElementById('radioRecusarPreco').disabled = true;
                    document.getElementById('radioRecusarPrecoDiv').classList.add('text-muted');
                    document.getElementById('avisoTentativas').style.display = 'block';
                    document.getElementById('radioRecusarOutro').checked = true;
                    divMotivoOutro.style.display = 'block';
                    inputMotivoOutro.required = true;
                } else {
                    document.getElementById('radioRecusarPreco').disabled = false;
                    document.getElementById('radioRecusarPrecoDiv').classList.remove('text-muted');
                    document.getElementById('avisoTentativas').style.display = 'none';
                    document.getElementById('radioRecusarPreco').checked = true;
                    divMotivoOutro.style.display = 'none';
                    inputMotivoOutro.required = false;
                }
            });
        });

    });
</script>

<?php include '../includes/footer.php'; ?>