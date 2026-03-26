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

$sql_hist = "SELECT s.data_hora, u.nome AS cliente_nome, p.titulo, s.status, o.valor_sessao, o.estimativa_tempo 
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
$cli_nome = $_GET['cli-nome'] ?? ''; // Novo campo de busca
$cli_inicio = $_GET['cli-data-inicio'] ?? '';
$cli_fim = $_GET['cli-data-fim'] ?? '';
$cli_status = $_GET['cli-status'] ?? ''; // <--- NOVO FILTRO DE STATUS AQUI
$cli_ordem = $_GET['cli-ordem'] ?? 'data_desc';

// Adicionada a subquery para contar as sessões e busca do ID/Status para bloqueio
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
if (!empty($cli_status)) { // <--- APLICA O FILTRO NO BANCO AQUI
    $sql_cli .= " AND u.status = ?";
    $params_cli[] = $cli_status;
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
        /* Força os botões de filtro a serem quadrados perfeitos */
        .btn-square-filtro {
            width: 36px !important;
            height: 36px !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            padding: 0 !important;
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

                        <button type="submit" class="btn btn-sm btn-primary btn-square-filtro" title="Aplicar Filtros">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <a href="relatorios-artista.php?aba=historico" class="btn btn-sm btn-outline-secondary btn-square-filtro" title="Limpar Filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Data</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Projeto</th>
                                <th scope="col">Duração (Est.)</th>
                                <th scope="col">Valor</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico_dados)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-white-50 py-4">Nenhuma sessão encontrada com os filtros atuais.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historico_dados as $hist):
                                    $data_sessao = new DateTime($hist['data_hora']);
                                    $badge_class = 'bg-primary';
                                    if ($hist['status'] == 'Concluído') $badge_class = 'bg-success';
                                    if ($hist['status'] == 'Cancelado') $badge_class = 'bg-danger';
                                ?>
                                    <tr>
                                        <td class="text-nowrap"><?php echo $data_sessao->format('d/m/Y - H:i'); ?></td>
                                        <td><?php echo htmlspecialchars($hist['cliente_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($hist['titulo']); ?></td>
                                        <td class="text-white-50 small"><?php echo htmlspecialchars($hist['estimativa_tempo'] ?? '-'); ?></td>
                                        <td>R$ <?php echo !empty($hist['valor_sessao']) ? number_format($hist['valor_sessao'], 2, ',', '.') : '-'; ?></td>
                                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($hist['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-clientes" role="tabpanel" aria-labelledby="clientes-tab">

                <form class="filtro-container mb-4 d-flex flex-wrap gap-2 align-items-end" method="GET">
                    <input type="hidden" name="aba" value="clientes">
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            if (window.location.search.includes('aba=clientes')) {
                                new bootstrap.Tab(document.querySelector('#clientes-tab')).show();
                            }
                        });
                    </script>

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

                        <button type="submit" class="btn btn-sm btn-primary btn-square-filtro" title="Aplicar Filtros">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <a href="relatorios-artista.php?aba=clientes" class="btn btn-sm btn-outline-secondary btn-square-filtro" title="Limpar Filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
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
                            <?php if (empty($clientes_dados)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-white-50 py-4">Nenhum cliente encontrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($clientes_dados as $cli):
                                    $data_cad = new DateTime($cli['data_cadastro']);
                                    $telefone_limpo = preg_replace('/[^0-9]/', '', $cli['telefone']);
                                    if (strlen($telefone_limpo) == 10 || strlen($telefone_limpo) == 11) {
                                        $telefone_limpo = '55' . $telefone_limpo;
                                    }
                                    $wpp_link = "https://api.whatsapp.com/send?phone=" . $telefone_limpo;

                                    // Verifica se o status no BD é 'Bloqueado'
                                    $is_bloqueado = (isset($cli['status']) && $cli['status'] === 'Bloqueado');
                                ?>
                                    <tr class="<?php echo $is_bloqueado ? 'opacity-50' : ''; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($cli['nome']); ?>

                                        </td>
                                        <td><?php echo htmlspecialchars($cli['email']); ?></td>
                                        <td class="text-nowrap">
                                            <?php echo htmlspecialchars($cli['telefone']); ?>
                                            <?php if (!empty($telefone_limpo)): ?>
                                                <a href="<?php echo $wpp_link; ?>" target="_blank" class="text-success ms-2 text-decoration-none" title="Conversar no WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold"><?php echo $cli['qtd_sessoes']; ?></td>
                                        <td><?php echo $data_cad->format('d/m/Y'); ?></td>
                                        <td class="text-end">
                                            <?php if ($is_bloqueado): ?>
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
        // Puxa o ID para o modal de Bloquear
        const btnsBloquear = document.querySelectorAll('.btn-bloquear-js');
        const inputBloquear = document.getElementById('inputBloquearClienteId');
        btnsBloquear.forEach(btn => {
            btn.addEventListener('click', function() {
                inputBloquear.value = this.getAttribute('data-id');
            });
        });

        // Puxa o ID para o modal de Desbloquear
        const btnsDesbloquear = document.querySelectorAll('.btn-desbloquear-js');
        const inputDesbloquear = document.getElementById('inputDesbloquearClienteId');
        btnsDesbloquear.forEach(btn => {
            btn.addEventListener('click', function() {
                inputDesbloquear.value = this.getAttribute('data-id');
            });
        });
    });
</script>
<?php include '../includes/footer.php'; ?>