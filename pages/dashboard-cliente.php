<?php
session_start();
require_once '../includes/conexao.php'; // conexao com o Clever Cloud

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// variaveis necessarias 
$id_usuario = $_SESSION['usuario_id'];
$titulo_pagina = "Meu Painel";

// consulta SQL pra localizar os 3 agendamentos mais proximos
try {
    $sql = "SELECT p.titulo, s.data_hora 
            FROM sessao s
            JOIN projeto p ON s.id_projeto = p.id_projeto
            WHERE p.id_usuario = ? 
            AND s.data_hora >= NOW() 
            AND s.status = 'Agendado'
            ORDER BY s.data_hora ASC 
            LIMIT 3";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_usuario]);
    $proximos_agendamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    $proximos_agendamentos = [];
}

// --- ADICIONAR DAQUI ---
try {
    $stmt_analise = $pdo->prepare("SELECT COUNT(*) FROM orcamento WHERE id_usuario = ? AND (status IN ('Pendente', 'Negociacao') OR status IS NULL)");
    $stmt_analise->execute([$id_usuario]);
    $qtd_orcamentos = $stmt_analise->fetchColumn();

    $stmt_acao1 = $pdo->prepare("SELECT COUNT(*) FROM orcamento WHERE id_usuario = ? AND status = 'Aguardando Aceite'");
    $stmt_acao1->execute([$id_usuario]);

    $stmt_acao2 = $pdo->prepare("SELECT COUNT(*) FROM projeto WHERE id_usuario = ? AND status = 'Agendamento Pendente'");
    $stmt_acao2->execute([$id_usuario]);
    $qtd_acoes_requeridas = $stmt_acao1->fetchColumn() + $stmt_acao2->fetchColumn();

    $stmt_sessoes = $pdo->prepare("SELECT COUNT(*) FROM sessao s JOIN projeto p ON s.id_projeto = p.id_projeto WHERE p.id_usuario = ? AND s.status = 'Agendado' AND s.data_hora >= NOW()");
    $stmt_sessoes->execute([$id_usuario]);
    $qtd_sessoes = $stmt_sessoes->fetchColumn();
} catch (PDOException $e) {
    $qtd_orcamentos = 0;
    $qtd_acoes_requeridas = 0;
    $qtd_sessoes = 0;
}

include '../includes/header.php';
?>

<?php
// menu lateral
$pagina_ativa = basename($_SERVER['PHP_SELF']);
$link_prefix = '';

echo '<div class="submenu-painel">';

if ($_SESSION['usuario_perfil'] == 'artista') {
    echo '<a href="dashboard-artista.php">Início</a>';
    echo '<a href="agenda.php">Agenda</a>';
    echo '<a href="portfolio-artista.php">Portfólio</a>';
    echo '<a href="relatorios-artista.php">Relatórios</a>';
    echo '<a href="configuracoes-artista.php">Configurações</a>';
} else {
    echo '<a href="dashboard-cliente.php" class="active">Início</a>';
    echo '<a href="agendamentos-cliente.php">Meus Agendamentos</a>';
    echo '<a href="solicitar-orcamento.php">Orçamento</a>';
    echo '<a href="configuracoes-cliente.php">Configurações</a>';
}
echo '</div>';
?>

<main>
    <div class="container my-5 py-5">
        <h2 class="text-center mb-5">BEM-VINDO, <?php echo strtoupper($_SESSION['usuario_nome']); ?></h2>

        <style>
            .card-hover {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .card-hover:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 15px rgba(13, 202, 240, 0.2);
                border-color: #0dcaf0 !important;
                cursor: pointer;
            }
        </style>

        <div class="row text-center mb-5">
            <div class="col-md-4 mb-4">
                <a href="agendamentos-cliente.php#acordeaoAcaoRequerida" class="card-resumo card-hover text-decoration-none text-light d-block <?php echo ($qtd_acoes_requeridas > 0) ? 'border-warning' : ''; ?>">
                    <h3 class="<?php echo ($qtd_acoes_requeridas > 0) ? 'text-warning' : ''; ?>"><?php echo $qtd_acoes_requeridas; ?></h3>
                    <p class="text-white-50 mb-0">Pendentes para Agendar</p>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="agendamentos-cliente.php?aba=proximas" class="card-resumo card-hover text-decoration-none text-light d-block">
                    <h3><?php echo $qtd_sessoes; ?></h3>
                    <p class="text-white-50 mb-0">Sessões Agendadas</p>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="agendamentos-cliente.php?aba=analise" class="card-resumo card-hover text-decoration-none text-light d-block">
                    <h3><?php echo $qtd_orcamentos; ?></h3>
                    <p class="text-white-50 mb-0">Orçamentos em Análise</p>
                </a>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <h3 class="mb-4">Próximos Agendamentos</h3>

                <?php if (empty($proximos_agendamentos)): ?>
                    <div class="card-resumo mb-3 text-center">
                        <p class="text-white-50 mb-0 py-3">Você ainda não possui sessões agendadas.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($proximos_agendamentos as $sessao): ?>
                        <div class="card-resumo mb-3 p-3">

                            <p class="mb-1 text-light"><strong><?php echo htmlspecialchars($sessao['titulo']); ?></strong></p>
                            <p class="text-white-50 small mb-0">
                                <i class="bi bi-calendar3 me-2"></i>
                                <?php
                                $data = new DateTime($sessao['data_hora']);
                                echo $data->format('d/m/Y - H:i');
                                ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <h3 class="mb-4 invisible">Ações</h3>
                <div class="card-resumo p-4">
                    <p class="text-white-50 mb-4">Acompanhe seus agendamentos ou solicite um novo orçamento.</p>
                    <div class="d-grid gap-2">
                        <a href="solicitar-orcamento.php" class="btn btn-primary">SOLICITAR ORÇAMENTO</a>
                        <a href="agendamentos-cliente.php" class="btn btn-outline-light">VER TODOS OS AGENDAMENTOS</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>