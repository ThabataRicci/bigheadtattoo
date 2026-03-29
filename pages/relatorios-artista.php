<?php
session_start();
require_once '../includes/conexao.php'; // Conecta ao banco de dados real

// Proteção de acesso
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'artista') {
    header("Location: login.php");
    exit();
}

$titulo_pagina = "Relatórios";

// --- LÓGICA DO FILTRO DE HISTÓRICO ---
$hist_inicio = $_GET['hist-data-inicio'] ?? '';
$hist_fim = $_GET['hist-data-fim'] ?? '';
$hist_status = $_GET['hist-status'] ?? '';
$hist_cliente = $_GET['hist-cliente'] ?? '';
$hist_projeto = $_GET['hist-projeto'] ?? '';
$hist_ordem = $_GET['hist-ordem'] ?? 'data_desc';

$sql_hist = "SELECT s.id_sessao, s.data_hora, p.id_projeto, p.titulo, s.status, 
                    COALESCE(s.valor_sessao, o.valor_sessao) AS valor_sessao, 
                    COALESCE(s.estimativa_tempo, o.estimativa_tempo) AS estimativa_tempo,
                    u.id_usuario, u.nome AS cliente_nome, u.email, u.telefone, u.data_cadastro, u.status AS cliente_status,
                    o.local_corpo, o.descricao_ideia, o.qtd_sessoes,
                    (SELECT COUNT(*) FROM sessao s2 JOIN projeto p2 ON s2.id_projeto = p2.id_projeto WHERE p2.id_usuario = u.id_usuario AND s2.status = 'Concluído') as qtd_sessoes_total,
                    (SELECT SUM(s3.valor_sessao) FROM sessao s3 JOIN projeto p3 ON s3.id_projeto = p3.id_projeto WHERE p3.id_usuario = u.id_usuario AND s3.status = 'Concluído') as total_gasto_cliente
             FROM sessao s 
             JOIN projeto p ON s.id_projeto = p.id_projeto 
             JOIN usuario u ON p.id_usuario = u.id_usuario 
             LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento 
             WHERE 1=1";
$params_hist = [];

if (!empty($hist_inicio)) {
    $sql_hist .= " AND DATE(s.data_hora) >= ?";
    $params_hist[] = $hist_inicio;
}
if (!empty($hist_fim)) {
    $sql_hist .= " AND DATE(s.data_hora) <= ?";
    $params_hist[] = $hist_fim;
}
if (!empty($hist_status)) {
    $sql_hist .= " AND s.status = ?";
    $params_hist[] = $hist_status;
}
if (!empty($hist_cliente)) {
    $sql_hist .= " AND u.nome LIKE ?";
    $params_hist[] = "%$hist_cliente%";
}
if (!empty($hist_projeto)) {
    $sql_hist .= " AND p.titulo LIKE ?";
    $params_hist[] = "%$hist_projeto%";
}

if ($hist_ordem == 'data_asc') {
    $sql_hist .= " ORDER BY s.data_hora ASC";
} elseif ($hist_ordem == 'status') {
    $sql_hist .= " ORDER BY s.status ASC, s.data_hora DESC";
} else {
    $sql_hist .= " ORDER BY s.data_hora DESC";
}

$stmt_hist = $pdo->prepare($sql_hist);
$stmt_hist->execute($params_hist);
$historico_dados = $stmt_hist->fetchAll();

// --- LÓGICA DO FILTRO DE CLIENTES ---
$cli_nome = $_GET['cli-nome'] ?? '';
$cli_inicio = $_GET['cli-data-inicio'] ?? '';
$cli_fim = $_GET['cli-data-fim'] ?? '';
$cli_status = $_GET['cli-status'] ?? '';
$cli_ordem = $_GET['cli-ordem'] ?? 'data_desc';

// Adicionada a subquery p contar as sessões e busca do ID/Status para bloqueio
$sql_cli = "SELECT u.id_usuario, u.nome, u.email, u.telefone, u.data_cadastro, u.status,
                   (SELECT COUNT(*) FROM sessao s JOIN projeto p ON s.id_projeto = p.id_projeto WHERE p.id_usuario = u.id_usuario AND s.status = 'Concluído') as qtd_sessoes
            FROM usuario u 
            WHERE u.perfil = 'cliente'";
$params_cli = [];

if (!empty($cli_nome)) {
    $sql_cli .= " AND u.nome LIKE ?";
    $params_cli[] = "%$cli_nome%";
}
if (!empty($cli_inicio)) {
    $sql_cli .= " AND DATE(u.data_cadastro) >= ?";
    $params_cli[] = $cli_inicio;
}
if (!empty($cli_fim)) {
    $sql_cli .= " AND DATE(u.data_cadastro) <= ?";
    $params_cli[] = $cli_fim;
}
if (!empty($cli_status)) {
    $sql_cli .= " AND u.status = ?";
    $params_cli[] = $cli_status;
} else {
}

if ($cli_ordem == 'data_asc') {
    $sql_cli .= " ORDER BY u.data_cadastro ASC";
} elseif ($cli_ordem == 'sessoes_desc') {
    $sql_cli .= " ORDER BY qtd_sessoes DESC";
} elseif ($cli_ordem == 'sessoes_asc') {
    $sql_cli .= " ORDER BY qtd_sessoes ASC";
} elseif ($cli_ordem == 'alfa') {
    $sql_cli .= " ORDER BY u.nome ASC";
} else {
    $sql_cli .= " ORDER BY u.data_cadastro DESC";
}

$stmt_cli = $pdo->prepare($sql_cli);
$stmt_cli->execute($params_cli);
$clientes_dados = $stmt_cli->fetchAll();

$itens_por_pagina = 14;

// Paginação: Histórico
$total_hist = count($historico_dados);
$pg_hist = isset($_GET['pg_hist']) ? max(1, intval($_GET['pg_hist'])) : 1;
$offset_hist = ($pg_hist - 1) * $itens_por_pagina;
$total_paginas_hist = ceil($total_hist / $itens_por_pagina);
$historico_paginado = array_slice($historico_dados, $offset_hist, $itens_por_pagina);

// Paginação: Clientes
$total_cli = count($clientes_dados);
$pg_cli = isset($_GET['pg_cli']) ? max(1, intval($_GET['pg_cli'])) : 1;
$offset_cli = ($pg_cli - 1) * $itens_por_pagina;
$total_paginas_cli = ceil($total_cli / $itens_por_pagina);
$clientes_paginado = array_slice($clientes_dados, $offset_cli, $itens_por_pagina);

include '../includes/header.php';
?>

<?php
// MENU DE NAVEGAÇÃO
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
    }
    echo '</div>';
}
?>

<main>
    <style>
        .btn-square-filtro {
            width: 36px !important;
            height: 36px !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
        }

        .custom-card {
            background: #1e1e1e;
            border: none;
            border-radius: 12px;
            transition: transform 0.2s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .custom-card:hover {
            transform: translateY(-5px);
        }

        .border-accent-blue {
            border-left: 5px solid #7a7a7a !important;
        }

        .border-accent-green {
            border-left: 5px solid #7a7a7a !important;
        }

        .border-accent-info {
            border-left: 5px solid #7a7a7a !important;
        }

        .card-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #888;
            display: block;
        }

        .card-value {
            font-size: 1.3rem;
            font-weight: 700;
            margin-top: 2px;
        }

        .btn-detalhes-cliente .icone-lupa,
        .btn-detalhes-projeto .icone-lupa,
        .btn-detalhes-projeto-interno .icone-lupa {
            color: #454545;
            font-size: 14px;
            transition: 0.5s;
        }

        .btn-detalhes-cliente:hover .icone-lupa,
        .btn-detalhes-projeto:hover .icone-lupa,
        .btn-detalhes-projeto-interno:hover .icone-lupa {
            color: #ffffff;
            transform: scale(1.2);
        }

        /* 4. Personalização da barra de rolagem dos projetos no modal */
        #mdlCliProjetos::-webkit-scrollbar {
            width: 6px;
        }

        #mdlCliProjetos::-webkit-scrollbar-track {
            background: #1e1e1e;
            /* Cor do fundo da trilha */
            border-radius: 4px;
        }

        #mdlCliProjetos::-webkit-scrollbar-thumb {
            background: #555;
            /* Cor da barrinha */
            border-radius: 4px;
        }

        #mdlCliProjetos::-webkit-scrollbar-thumb:hover {
            background: #0dcaf0;
            /* Fica azulzinho claro ao passar o mouse */
        }

        .tabela-fixa {
            height: 700px;
            overflow-y: auto;
        }

        .tabela-fixa::-webkit-scrollbar {
            width: 8px;
        }

        .tabela-fixa::-webkit-scrollbar-track {
            background: #1e1e1e;
            border-radius: 4px;
        }

        .tabela-fixa::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }

        .tabela-fixa::-webkit-scrollbar-thumb:hover {
            background: #777;
        }

        /* 1. Borda dos botões NÃO CLICADOS (padrão) */
        .pagination .page-link {
            background-color: transparent !important;
            border: 1px solid #2c2c2c !important;
            color: #aaa !important;
            margin: 0 4px;
            border-radius: 6px !important;
            transition: all 0.3s ease;
        }

        /* Efeito ao passar o mouse */
        .pagination .page-link:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: #ffffff !important;
            border-color: #777 !important;
        }

        /* Botão da página ATUAL */
        .pagination .page-item.active .page-link {
            background-color: transparent !important;
            color: #ffffff !important;
            border-color: #ffffff !important;
            font-weight: bold;
        }

        /* 2. Borda dos botões INATIVOS */
        .pagination .page-item.disabled .page-link {
            background-color: transparent !important;
            color: #777 !important;
            border-color: #2c2c2c !important;
            cursor: not-allowed;
        }

        /* Novas classes para o status dinâmico sem fundo no modal de clientes */
        .status-modal-ativo {
            color: #28a745 !important;
            /* Verde Bootstrap padrão (contrasta bem) */
            background: transparent !important;
            font-weight: bold;
            padding: 0 !important;
            /* Tira o preenchimento de 'caixinha' */
            border: none;
        }

        .status-modal-bloqueado {
            color: #dc3545 !important;
            /* Vermelho Bootstrap padrão (contrasta bem) */
            background: transparent !important;
            font-weight: bold;
            padding: 0 !important;
            /* Tira o preenchimento de 'caixinha' */
            border: none;
        }
    </style>

    <div class="container my-5 py-5">
        <h2 class="text-center mb-5">RELATÓRIOS</h2>

        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 'bloqueado'): ?>
            <div class="alert alert-warning text-center alert-dismissible fade show" role="alert">
                <i class="bi bi-slash-circle me-2"></i> Cliente bloqueado. Ele não poderá mais fazer login.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_GET['sucesso']) && $_GET['sucesso'] == 'desbloqueado'): ?>
            <div class="alert alert-success text-center alert-dismissible fade show" role="alert">
                <i class="bi bi-unlock me-2"></i> Cliente desbloqueado.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs nav-tabs-dark mb-0" id="abasRelatorios" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button" role="tab" aria-controls="tab-historico" aria-selected="true">Histórico de Sessões</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clientes-tab" data-bs-toggle="tab" data-bs-target="#tab-clientes" type="button" role="tab" aria-controls="tab-clientes" aria-selected="false">Clientes Cadastrados</button>
            </li>
        </ul>

        <div class="tab-content" id="abasRelatoriosConteudo">

            <div class="tab-pane fade show active" id="tab-historico" role="tabpanel" aria-labelledby="historico-tab">

                <form class="filtro-container mb-4 d-flex flex-wrap gap-2 align-items-end" method="GET">
                    <input type="hidden" name="aba" value="historico">

                    <div class="filtro-item flex-grow-1">
                        <label class="form-label small mb-1">Cliente:</label>
                        <input type="text" class="form-control form-control-sm" name="hist-cliente" value="<?php echo htmlspecialchars($hist_cliente); ?>">
                    </div>
                    <div class="filtro-item flex-grow-1">
                        <label class="form-label small mb-1">Projeto:</label>
                        <input type="text" class="form-control form-control-sm" name="hist-projeto" value="<?php echo htmlspecialchars($hist_projeto); ?>">
                    </div>
                    <div class="filtro-item">
                        <label class="form-label small mb-1">Status:</label>
                        <select class="form-select form-select-sm bg-dark text-light border-secondary" name="hist-status" style="background-color: #2c2c2c !important;">
                            <option value="">Todos</option>
                            <option value="Agendado" <?php if ($hist_status == 'Agendado') echo 'selected'; ?>>Agendado</option>
                            <option value="Concluído" <?php if ($hist_status == 'Concluído') echo 'selected'; ?>>Concluído</option>
                            <option value="Cancelado" <?php if ($hist_status == 'Cancelado') echo 'selected'; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="filtro-item">
                        <label class="form-label small mb-1">De:</label>
                        <input type="date" class="form-control form-control-sm" name="hist-data-inicio" value="<?php echo htmlspecialchars($hist_inicio); ?>">
                    </div>
                    <div class="filtro-item">
                        <label class="form-label small mb-1">Até:</label>
                        <input type="date" class="form-control form-control-sm" name="hist-data-fim" value="<?php echo htmlspecialchars($hist_fim); ?>">
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary btn-square-filtro" title="Aplicar Filtros">
                        <i class="bi bi-check-lg"></i>
                    </button>
                    <a href="relatorios-artista.php?aba=historico" class="btn btn-sm btn-outline-secondary btn-square-filtro" title="Limpar Filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>

                    <div class="d-flex gap-2 align-items-end ms-auto">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light btn-square-filtro" type="button" data-bs-toggle="dropdown" title="Ordenar">
                                <i class="bi bi-sort-down"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><button type="submit" name="hist-ordem" value="data_desc" class="dropdown-item <?php if ($hist_ordem == 'data_desc') echo 'active'; ?>">Mais recentes</button></li>
                                <li><button type="submit" name="hist-ordem" value="data_asc" class="dropdown-item <?php if ($hist_ordem == 'data_asc') echo 'active'; ?>">Mais antigas</button></li>
                                <li><button type="submit" name="hist-ordem" value="status" class="dropdown-item <?php if ($hist_ordem == 'status') echo 'active'; ?>">Por Status</option>
                                </li>
                            </ul>
                        </div>

                        <button type="button" onclick="exportarParaExcel('tab-historico', 'relatorio_sessoes')" class="btn btn-sm btn-outline-success btn-square-filtro" title="Exportar Excel">
                            <i class="bi bi-file-earmark-excel"></i>
                        </button>
                    </div>
                </form>

                <div class="table-responsive tabela-fixa">
                    <table class="table table-dark table-hover align-middle">
                        <thead class="sticky-top bg-dark">
                            <tr>
                                <th scope="col">Data</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Projeto</th>
                                <th scope="col">Duração</th>
                                <th scope="col">Valor</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico_paginado)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-white-50 py-4">Nenhuma sessão encontrada.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historico_paginado as $hist):
                                    $data_sessao = new DateTime($hist['data_hora']);
                                    $badge_class = ($hist['status'] == 'Concluído') ? 'bg-success' : (($hist['status'] == 'Cancelado') ? 'bg-danger' : 'bg-primary');
                                ?>
                                    <tr>
                                        <td class="text-nowrap"><?php echo $data_sessao->format('d/m/Y | H:i'); ?></td>
                                        <td>
                                            <a href="#" class="text-light text-decoration-none btn-detalhes-cliente"
                                                data-id="<?php echo $hist['id_usuario']; ?>"
                                                data-nome="<?php echo htmlspecialchars($hist['cliente_nome']); ?>"
                                                data-email="<?php echo htmlspecialchars($hist['email']); ?>"
                                                data-telefone="<?php echo htmlspecialchars($hist['telefone']); ?>"
                                                data-cadastro="<?php echo date('d/m/Y', strtotime($hist['data_cadastro'])); ?>"
                                                data-sessoes="<?php echo $hist['qtd_sessoes_total']; ?>"
                                                data-gasto="<?php echo number_format($hist['total_gasto_cliente'] ?? 0, 2, ',', '.'); ?>"
                                                data-status="<?php echo $hist['cliente_status']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalDetalhesCliente">
                                                <?php echo htmlspecialchars($hist['cliente_nome']); ?><i class="bi bi-search ms-1 icone-lupa"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="#" class="text-light text-decoration-none btn-detalhes-projeto"
                                                data-id-proj="<?php echo $hist['id_projeto']; ?>"
                                                data-titulo="<?php echo htmlspecialchars($hist['titulo']); ?>"
                                                data-local="<?php echo htmlspecialchars($hist['local_corpo'] ?? 'Não informado'); ?>"
                                                data-ideia="<?php echo htmlspecialchars($hist['descricao_ideia'] ?? 'Não informada'); ?>"
                                                data-duracao="<?php echo htmlspecialchars($hist['estimativa_tempo'] ?? 'A definir'); ?>"
                                                data-sessoes="<?php echo htmlspecialchars($hist['qtd_sessoes'] ?? '-'); ?>"

                                                data-cli-id="<?php echo $hist['id_usuario']; ?>"
                                                data-cli-nome="<?php echo htmlspecialchars($hist['cliente_nome']); ?>"
                                                data-cli-email="<?php echo htmlspecialchars($hist['email']); ?>"
                                                data-cli-telefone="<?php echo htmlspecialchars($hist['telefone']); ?>"
                                                data-cli-cadastro="<?php echo date('d/m/Y', strtotime($hist['data_cadastro'])); ?>"
                                                data-cli-sessoes="<?php echo $hist['qtd_sessoes_total']; ?>"
                                                data-cli-gasto="<?php echo number_format($hist['total_gasto_cliente'] ?? 0, 2, ',', '.'); ?>"
                                                data-cli-status="<?php echo $hist['cliente_status']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalDetalhesProjeto">
                                                <?php echo htmlspecialchars($hist['titulo']); ?> <i class="bi bi-search ms-1 icone-lupa"></i>
                                            </a>
                                        </td>
                                        <td class="text-white-50 small"><?php echo htmlspecialchars($hist['estimativa_tempo'] ?? '-'); ?></td>
                                        <td>R$ <?php echo !empty($hist['valor_sessao']) ? number_format($hist['valor_sessao'], 2, ',', '.') : '-'; ?></td>
                                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($hist['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas_hist > 0):
                    $qs_hist = $_GET;
                    unset($qs_hist['pg_hist']);
                    $url_base_hist = '?' . http_build_query($qs_hist) . (!empty($qs_hist) ? '&' : '') . 'pg_hist=';
                ?>
                    <nav class="mt-4 mb-3">
                        <ul class="pagination pagination-sm justify-content-center">
                            <li class="page-item <?php echo ($pg_hist <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link bg-dark text-light border-secondary" href="<?php echo $url_base_hist . ($pg_hist - 1); ?>">Anterior</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_paginas_hist; $i++): ?>
                                <li class="page-item <?php echo ($pg_hist == $i) ? 'active' : ''; ?>">
                                    <a class="page-link border-secondary <?php echo ($pg_hist == $i) ? 'bg-secondary text-white' : 'bg-dark text-light'; ?>" href="<?php echo $url_base_hist . $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($pg_hist >= $total_paginas_hist) ? 'disabled' : ''; ?>">
                                <a class="page-link bg-dark text-light border-secondary" href="<?php echo $url_base_hist . ($pg_hist + 1); ?>">Próxima</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

                <hr class="border-secondary my-4"> <?php
                                                    $total_faturado = 0;
                                                    $sessoes_concluidas = 0;

                                                    foreach ($historico_dados as $d) {
                                                        if ($d['status'] == 'Concluído') {
                                                            $total_faturado += $d['valor_sessao'];
                                                            $sessoes_concluidas++;
                                                        }
                                                    }
                                                    ?>
                <div class="row g-4 mt-3 justify-content-center">
                    <div class="col-md-6">
                        <div class="card custom-card border-accent-blue p-3 text-center">
                            <span class="card-label">Sessões Concluídas</span>
                            <h4 class="card-value text-light mb-0"><?php echo $sessoes_concluidas; ?></h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card custom-card border-accent-green p-3 text-center">
                            <span class="card-label">Faturamento Concluído</span>
                            <h4 class="card-value text-light mb-0">R$ <?php echo number_format($total_faturado, 2, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-clientes" role="tabpanel" aria-labelledby="clientes-tab">

                <form class="filtro-container mb-4 d-flex flex-wrap gap-2 align-items-end" method="GET">
                    <input type="hidden" name="aba" value="clientes">

                    <div class="filtro-item flex-grow-1">
                        <label class="form-label small mb-1">Nome do Cliente:</label>
                        <input type="text" class="form-control form-control-sm" name="cli-nome" value="<?php echo htmlspecialchars($cli_nome); ?>">
                    </div>

                    <div class="filtro-item flex-grow-1">
                        <label class="form-label small mb-1">Status Cliente:</label>
                        <select class="form-select form-select-sm bg-dark text-light border-secondary" name="cli-status" style="background-color: #2c2c2c !important;">
                            <option value="">Todos</option>
                            <option value="Ativo" <?php if ($cli_status == 'Ativo') echo 'selected'; ?>>Ativos</option>
                            <option value="Bloqueado" <?php if ($cli_status == 'Bloqueado') echo 'selected'; ?>>Bloqueados</option>
                            <option value="Excluido" <?php if ($cli_status == 'Excluido') echo 'selected'; ?>>Contas Excluídas</option>
                        </select>
                    </div>

                    <div class="filtro-item flex-grow-1">
                        <label class="form-label small mb-1">Cadastrados de:</label>
                        <input type="date" class="form-control form-control-sm" name="cli-data-inicio" value="<?php echo htmlspecialchars($cli_inicio); ?>">
                    </div>
                    <div class="filtro-item flex-grow-1">
                        <label class="form-label small mb-1">Até:</label>
                        <input type="date" class="form-control form-control-sm" name="cli-data-fim" value="<?php echo htmlspecialchars($cli_fim); ?>">
                    </div>

                    <div class="d-flex gap-2 align-items-end ms-auto">


                        <button type="submit" class="btn btn-sm btn-primary btn-square-filtro" title="Aplicar Filtros">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <a href="relatorios-artista.php?aba=clientes" class="btn btn-sm btn-outline-secondary btn-square-filtro" title="Limpar Filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>

                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light btn-square-filtro" type="button" data-bs-toggle="dropdown" title="Ordenar">
                                <i class="bi bi-sort-down"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><button type="submit" name="cli-ordem" value="data_desc" class="dropdown-item <?php if ($cli_ordem == 'data_desc') echo 'active'; ?>">Mais Recentes</button></li>
                                <li><button type="submit" name="cli-ordem" value="data_asc" class="dropdown-item <?php if ($cli_ordem == 'data_asc') echo 'active'; ?>">Mais Antigos</button></li>
                                <li><button type="submit" name="cli-ordem" value="sessoes_desc" class="dropdown-item <?php if ($cli_ordem == 'sessoes_desc') echo 'active'; ?>">Mais Sessões</button></li>
                                <li><button type="submit" name="cli-ordem" value="sessoes_asc" class="dropdown-item <?php if ($cli_ordem == 'sessoes_asc') echo 'active'; ?>">Menos Sessões</button></li>
                                <li><button type="submit" name="cli-ordem" value="alfa" class="dropdown-item <?php if ($cli_ordem == 'alfa') echo 'active'; ?>">Alfabético</button></li>
                            </ul>
                        </div>

                        <button type="button" onclick="exportarParaExcel('tab-clientes', 'lista_clientes')" class="btn btn-sm btn-outline-success btn-square-filtro" title="Exportar Lista">
                            <i class="bi bi-file-earmark-excel"></i>
                        </button>
                    </div>
                </form>

                <div class="table-responsive tabela-fixa">
                    <table class="table table-dark table-hover align-middle">
                        <thead class="sticky-top bg-dark">
                            <tr>
                                <th scope="col">Nome</th>
                                <th scope="col">E-mail</th>
                                <th scope="col">Telefone</th>
                                <th scope="col" class="text-center">Sessões Realizadas</th>
                                <th scope="col">Data Cadastro</th>
                                <th scope="col" class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clientes_paginado)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-white-50 py-4">Nenhum cliente encontrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($clientes_paginado as $cli):
                                    $data_cad = new DateTime($cli['data_cadastro']);
                                    $telefone_limpo = preg_replace('/[^0-9]/', '', $cli['telefone']);
                                    if (strlen($telefone_limpo) == 10 || strlen($telefone_limpo) == 11) {
                                        $telefone_limpo = '55' . $telefone_limpo;
                                    }
                                    $wpp_link = "https://api.whatsapp.com/send?phone=" . $telefone_limpo;

                                    $is_bloqueado = (isset($cli['status']) && $cli['status'] === 'Bloqueado');
                                    $is_excluido = (isset($cli['status']) && $cli['status'] === 'Excluido');

                                    $opacidade = ($is_bloqueado || $is_excluido) ? 'opacity-25' : '';
                                ?>
                                    <tr>
                                        <td class="<?php echo $opacidade; ?>">
                                            <?php echo htmlspecialchars($cli['nome']); ?>
                                        </td>
                                        <td class="text-nowrap <?php echo $opacidade; ?>">
                                            <?php echo htmlspecialchars($cli['email']); ?>
                                            <?php if (!$is_excluido): ?>
                                                <button type="button" class="btn btn-sm border-0 py-0 px-1 ms-1" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($cli['email']); ?>').then(() => alert('E-mail copiado!'));" title="Copiar E-mail">
                                                    <i class="bi bi-copy" style="font-size: 0.85rem; color: #aaa;"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-nowrap <?php echo $opacidade; ?>">
                                            <?php echo htmlspecialchars($cli['telefone']); ?>
                                            <?php if (!empty($telefone_limpo) && !$is_excluido): ?>
                                                <a href="<?php echo $wpp_link; ?>" target="_blank" class="text-success ms-2 text-decoration-none" title="Conversar no WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold <?php echo $opacidade; ?>"><?php echo $cli['qtd_sessoes']; ?></td>
                                        <td class="<?php echo $opacidade; ?>"><?php echo $data_cad->format('d/m/Y'); ?></td>

                                        <td class="text-end">
                                            <?php if ($cli['status'] === 'Excluido'): ?>
                                                <span class="btn btn-sm btn-outline-secondary disabled" style="opacity: 3;">
                                                    <i class="bi bi-x-circle me-1"></i> Conta Encerrada
                                                </span>
                                            <?php elseif ($is_bloqueado): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info btn-desbloquear-js" data-id="<?php echo $cli['id_usuario']; ?>" data-bs-toggle="modal" data-bs-target="#modalDesbloquearCliente"><i class="bi bi-unlock"></i> Desbloquear</button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-bloquear-js" data-id="<?php echo $cli['id_usuario']; ?>" data-bs-toggle="modal" data-bs-target="#modalBloquearCliente"><i class="bi bi-slash-circle"></i> Bloquear</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas_cli > 0):
                    $qs_cli = $_GET;
                    unset($qs_cli['pg_cli']);
                    $url_base_cli = '?' . http_build_query($qs_cli) . (!empty($qs_cli) ? '&' : '') . 'pg_cli=';
                ?>
                    <nav class="mt-4 mb-3">
                        <ul class="pagination pagination-sm justify-content-center">
                            <li class="page-item <?php echo ($pg_cli <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link bg-dark text-light border-secondary" href="<?php echo $url_base_cli . ($pg_cli - 1); ?>">Anterior</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_paginas_cli; $i++): ?>
                                <li class="page-item <?php echo ($pg_cli == $i) ? 'active' : ''; ?>">
                                    <a class="page-link border-secondary <?php echo ($pg_cli == $i) ? 'bg-secondary text-white' : 'bg-dark text-light'; ?>" href="<?php echo $url_base_cli . $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($pg_cli >= $total_paginas_cli) ? 'disabled' : ''; ?>">
                                <a class="page-link bg-dark text-light border-secondary" href="<?php echo $url_base_cli . ($pg_cli + 1); ?>">Próxima</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                <hr class="border-secondary my-4">

                <?php
                $total_clientes = count($clientes_dados);
                $clientes_ativos = 0;
                foreach ($clientes_dados as $c) {
                    // Só conta como ativo se NÃO for Bloqueado e NÃO for Excluido
                    if (!in_array(($c['status'] ?? ''), ['Bloqueado', 'Excluido'])) {
                        $clientes_ativos++;
                    }
                }
                ?>

                <div class="row g-4 mt-3 justify-content-center">
                    <div class="col-md-6">
                        <div class="card custom-card border-accent-blue p-3 text-center">
                            <span class="card-label">Total de Cadastros</span>
                            <h4 class="card-value text-light mb-0"><?php echo $total_clientes; ?></h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card custom-card border-accent-info p-3 text-center">
                            <span class="card-label">Clientes Ativos</span>
                            <h4 class="card-value text-light mb-0"><?php echo $clientes_ativos; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    </div>

    </div>
    </div>

    <div class="modal fade" id="modalDetalhesProjeto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light bg-dark border-secondary">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title text-white" id="mdlProjTitulo">Título</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <p class="mb-1 text-white-50">
                        <i class="bi bi-person me-1"></i>
                        <a href="#" id="mdlProjLinkCliente" class="text-light text-decoration-none btn-detalhes-cliente" data-bs-toggle="modal" data-bs-target="#modalDetalhesCliente" data-bs-dismiss="modal">
                            <span id="mdlProjNomeClienteTexto"></span> <i class="bi bi-search ms-1 icone-lupa"></i>
                        </a>
                    </p>
                    <p class="mb-1"><strong>Local do Corpo:</strong> <span id="mdlProjLocal"></span></p>
                    <p class="mb-1"><strong>Duração Estimada:</strong> <span id="mdlProjDuracao"></span></p>
                    <p class="mb-3"><strong>Sessões Estimadas:</strong> <span id="mdlProjSessoes"></span></p>
                    <p class="mb-3"><strong>Ideia do Cliente:</strong> "<span id="mdlProjIdeia"></span>"</p>

                    <h6 class="text-light mt-4 mb-3 border-bottom border-secondary pb-2">Histórico de Sessões:</h6>
                    <div id="mdlProjHistorico" class="small p-0" style="background-color: transparent;">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary p-0 pt-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetalhesCliente" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light bg-dark border-secondary">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="mdlCliNome">Nome</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <div class="row mb-3">
                        <div class="col-6">
                            <span class="card-label" style="font-size: 0.7rem;">E-mail</span>
                            <div class="d-flex align-items-center">
                                <span id="mdlCliEmail" class="text-light small me-2"></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary border-0 py-0 px-1" onclick="copiarEmail()" title="Copiar E-mail">
                                    <i class="bi bi-copy" style="font-size: 0.8rem; color: #aaa;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-6">
                            <span class="card-label" style="font-size: 0.7rem;">Telefone / WhatsApp</span>
                            <a href="" id="mdlCliWppLink" target="_blank" class="text-light text-decoration-none d-block">
                                <i class="bi bi-whatsapp me-1"></i><span id="mdlCliTelefone"></span>
                            </a>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <span class="card-label" style="font-size: 0.7rem;">Sessões Concluídas</span>
                            <span id="mdlCliSessoes" class="text-light fw-bold"></span>
                        </div>
                        <div class="col-6">
                            <span class="card-label" style="font-size: 0.7rem;">Total Investido</span>
                            <span class="text-light fw-bold">R$ <span id="mdlCliTotalGasto"></span></span>
                        </div>
                    </div>

                    <hr class="border-secondary">

                    <h6 class="text-light small fw-bold mb-2 border-bottom border-secondary pb-2">PROJETOS DESTE CLIENTE:</h6>
                    <div id="mdlCliProjetos" class="mb-3 pe-2" style="max-height: 150px; overflow-y: auto;">
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">Cadastrado em: <span id="mdlCliCadastro"></span></span>
                        <span id="mdlCliStatus" class="badge"></span>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary pt-3">
                    <div id="mdlCliAreaBotao"></div>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBloquearCliente" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light bg-dark border-secondary">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title text-danger">Bloquear Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <p>Isso impedirá o cliente de fazer login no sistema. Tem certeza que deseja bloquear o acesso dele?</p>
                    <form action="../actions/a.bloquear-cliente.php" method="POST">
                        <input type="hidden" name="id_cliente" id="inputBloquearClienteId" value="">
                        <input type="hidden" name="acao" value="bloquear">
                        <div class="modal-footer border-top border-secondary p-0 pt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Bloquear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDesbloquearCliente" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light bg-dark border-secondary">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title text-info">Desbloquear Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white-50">
                    <p>Isso reativará o acesso do cliente ao sistema. Deseja continuar?</p>
                    <form action="../actions/a.bloquear-cliente.php" method="POST">
                        <input type="hidden" name="id_cliente" id="inputDesbloquearClienteId" value="">
                        <input type="hidden" name="acao" value="desbloquear">
                        <div class="modal-footer border-top border-secondary p-0 pt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-info text-dark">Desbloquear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. LÓGICA DE TROCA DE ABAS ---
        if (window.location.search.includes('aba=clientes')) {
            const tabEl = document.querySelector('#clientes-tab');
            if (tabEl) new bootstrap.Tab(tabEl).show();
        }

        // --- 2. LÓGICA DE BLOQUEIO/DESBLOQUEIO ---
        window.configurarBotoesAcao = function() {
            document.querySelectorAll('.btn-bloquear-js').forEach(btn => {
                btn.onclick = function() {
                    document.getElementById('inputBloquearClienteId').value = this.getAttribute('data-id');
                };
            });
            document.querySelectorAll('.btn-desbloquear-js').forEach(btn => {
                btn.onclick = function() {
                    document.getElementById('inputDesbloquearClienteId').value = this.getAttribute('data-id');
                };
            });
        };
        configurarBotoesAcao();

        // --- 3. CARREGAMENTO AJAX: MODAL CLIENTE ---
        window.abrirModalCliente = function(btn) {
            const idCli = btn.getAttribute('data-id');
            const status = btn.getAttribute('data-status');

            document.getElementById('mdlCliNome').innerText = btn.getAttribute('data-nome');
            document.getElementById('mdlCliEmail').innerText = btn.getAttribute('data-email');
            document.getElementById('mdlCliTelefone').innerText = btn.getAttribute('data-telefone');
            document.getElementById('mdlCliTotalGasto').innerText = btn.getAttribute('data-gasto');
            document.getElementById('mdlCliSessoes').innerText = btn.getAttribute('data-sessoes');
            document.getElementById('mdlCliCadastro').innerText = btn.getAttribute('data-cadastro');

            const foneLimpo = btn.getAttribute('data-telefone').replace(/\D/g, '');
            document.getElementById('mdlCliWppLink').href = "https://wa.me/55" + foneLimpo;

            const badgeStatus = document.getElementById('mdlCliStatus');
            badgeStatus.innerText = status;
            badgeStatus.className = (status === 'Ativo') ? 'status-modal-ativo' : 'status-modal-bloqueado';

            // Botão de ação dinâmico dentro do modal
            const areaBotao = document.getElementById('mdlCliAreaBotao');
            if (status === 'Excluido') {
                areaBotao.innerHTML = `<span class="text-white-50 small me-3"><i class="bi bi-person-x me-1"></i> Esta conta foi excluída</span>`;
            } else if (status === 'Ativo') {
                areaBotao.innerHTML = `<button type="button" class="btn btn-sm btn-outline-danger btn-bloquear-js" data-id="${idCli}" data-bs-toggle="modal" data-bs-target="#modalBloquearCliente"><i class="bi bi-slash-circle me-1"></i>Bloquear Cliente</button>`;
            } else {
                // Status Bloqueado: Exibe a mensagem E o botão de desbloquear
                areaBotao.innerHTML = `
                    <span class="text-warning small me-3"><i class="bi bi-exclamation-triangle me-1"></i> Esta conta foi bloqueada</span>
                    <button type="button" class="btn btn-sm btn-outline-info btn-desbloquear-js" data-id="${idCli}" data-bs-toggle="modal" data-bs-target="#modalDesbloquearCliente"><i class="bi bi-unlock me-1"></i>Desbloquear</button>
                `;
            }
            configurarBotoesAcao();

            const areaProjetos = document.getElementById('mdlCliProjetos');
            areaProjetos.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';

            fetch(`../actions/busca_detalhes_ajax.php?tipo=projetos_cliente&id=${idCli}`)
                .then(r => r.text())
                .then(html => {
                    areaProjetos.innerHTML = html;
                });
        };

        // --- 4. CARREGAMENTO AJAX: MODAL PROJETO ---
        window.abrirModalProjeto = function(btn) {
            const idProj = btn.getAttribute('data-id-proj');
            document.getElementById('mdlProjTitulo').innerText = btn.getAttribute('data-titulo');
            document.getElementById('mdlProjLocal').innerText = btn.getAttribute('data-local');
            document.getElementById('mdlProjDuracao').innerText = btn.getAttribute('data-duracao');
            document.getElementById('mdlProjSessoes').innerText = btn.getAttribute('data-sessoes');
            document.getElementById('mdlProjIdeia').innerText = btn.getAttribute('data-ideia');

            const linkCli = document.getElementById('mdlProjLinkCliente');
            document.getElementById('mdlProjNomeClienteTexto').innerText = btn.getAttribute('data-cli-nome');

            const attrs = ['id', 'nome', 'email', 'telefone', 'cadastro', 'sessoes', 'gasto', 'status'];
            attrs.forEach(a => {
                linkCli.setAttribute('data-' + a, btn.getAttribute('data-cli-' + a));
            });

            const areaHist = document.getElementById('mdlProjHistorico');
            areaHist.innerHTML = '<div class="spinner-border spinner-border-sm text-secondary"></div>';

            fetch(`../actions/busca_detalhes_ajax.php?tipo=historico_projeto&id=${idProj}`)
                .then(r => r.text())
                .then(html => {
                    areaHist.innerHTML = html;
                });
        };

        // --- 5. DELEGAÇÃO DE EVENTOS (Resolve o problema de não abrir) ---
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.btn-detalhes-cliente, .btn-detalhes-projeto, .btn-detalhes-projeto-interno');
            if (!target) return;

            if (target.classList.contains('btn-detalhes-cliente')) {
                abrirModalCliente(target);
            } else if (target.classList.contains('btn-detalhes-projeto') || target.classList.contains('btn-detalhes-projeto-interno')) {
                abrirModalProjeto(target);
            }
        });
    });

    function copiarEmail() {
        const emailText = document.getElementById('mdlCliEmail').innerText;
        navigator.clipboard.writeText(emailText).then(() => {
            alert('E-mail copiado: ' + emailText);
        });
    }

    function exportarParaExcel(idTabPane, nomeArquivo) {
        const abaAtiva = document.getElementById(idTabPane);
        const tabelaOriginal = abaAtiva.querySelector('table');
        if (!tabelaOriginal) return;
        const tabelaClone = tabelaOriginal.cloneNode(true);
        tabelaClone.querySelectorAll('a').forEach(link => {
            link.outerHTML = link.innerText;
        });
        if (idTabPane === 'tab-clientes') {
            tabelaClone.querySelectorAll('tbody tr').forEach(linha => {
                let tdAcao = linha.querySelector('td:last-child');
                if (tdAcao) {
                    if (tdAcao.querySelector('.btn-bloquear-js')) tdAcao.innerText = 'Ativo';
                    else if (tdAcao.innerText.includes('Encerrada')) tdAcao.innerText = 'Excluído';
                    else tdAcao.innerText = 'Bloqueado';
                }
            });
        }
        const html = tabelaClone.outerHTML.replace(/ /g, '%20');
        const dataUri = 'data:application/vnd.ms-excel;charset=utf-8,' + html;
        const a = document.createElement("a");
        a.href = dataUri;
        a.download = nomeArquivo + '.xls';
        a.click();
    }
</script>
<?php include '../includes/footer.php'; ?>