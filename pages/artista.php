<?php
session_start();
require_once '../includes/conexao.php';

// 1. BUSCAR DADOS DO ARTISTA
$sql_artista = "SELECT id_usuario, nome, biografia, foto_perfil FROM usuario WHERE perfil = 'artista' LIMIT 1";
$stmt_artista = $pdo->query($sql_artista);
$dados_artista = $stmt_artista->fetch();

// 2. BUSCAR ESPECIALIDADES (ESTILOS)
$especialidades = "Nenhum estilo definido";
if ($dados_artista) {
    $sql_estilos = "SELECT e.nome FROM estilo e 
                    JOIN artista_estilo ae ON e.id_estilo = ae.id_estilo 
                    WHERE ae.id_artista = ?";
    $stmt_estilos = $pdo->prepare($sql_estilos);
    $stmt_estilos->execute([$dados_artista['id_usuario']]);
    $lista_estilos = $stmt_estilos->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($lista_estilos)) {
        $especialidades = implode(", ", $lista_estilos);
    }

    // 3. BUSCAR 4 TRABALHOS RECENTES PARA A VITRINE DA PÁGINA
    $sql_vitrine = "SELECT p.titulo, p.imagem, e.nome as estilo_nome 
                    FROM portfolio p 
                    INNER JOIN estilo e ON p.id_estilo = e.id_estilo 
                    WHERE p.id_artista = ? 
                    ORDER BY p.data_publicacao DESC LIMIT 4";
    $stmt_vitrine = $pdo->prepare($sql_vitrine);
    $stmt_vitrine->execute([$dados_artista['id_usuario']]);
    $trabalhos_vitrine = $stmt_vitrine->fetchAll();
}

$titulo_pagina = "O Artista";
include '../includes/header.php';
?>

<?php
// MENU DE NAVEGAÇÃO
if (isset($_SESSION['usuario_id'])) {
    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    echo '<div class="submenu-painel">';

    if ($_SESSION['usuario_perfil'] == 'artista') {
        echo '<a href="dashboard-artista.php" class="' . ($pagina_ativa == 'dashboard-artista.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="agenda.php">Agenda</a>';
        echo '<a href="portfolio-artista.php">Portfólio</a>';
        echo '<a href="relatorios-artista.php">Relatórios</a>';
        echo '<a href="configuracoes-artista.php">Configurações</a>';
    } else {
        echo '<a href="dashboard-cliente.php" class="' . ($pagina_ativa == 'dashboard-cliente.php' ? 'active' : '') . '">Início</a>';
        echo '<a href="agendamentos-cliente.php">Meus Agendamentos</a>';
        echo '<a href="solicitar-orcamento.php">Orçamento</a>';
        echo '<a href="configuracoes-cliente.php">Configurações</a>';
    }
    echo '</div>';
}
?>

<main>
    <div class="container my-5 py-5">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <?php
                // Verifica se há foto de perfil, senão usa uma padrão
                $foto = !empty($dados_artista['foto_perfil']) ? "../imagens/perfil/" . $dados_artista['foto_perfil'] : "../imagens/foto_artista.png";
                ?>
                <img src="<?php echo $foto; ?>" class="img-fluid rounded-circle mb-4 foto-perfil-artista shadow" alt="Foto do Artista" style="width: 300px; height: 300px; object-fit: cover;">
            </div>
            <div class="col-md-8">
                <h2 class="display-4"><?php echo strtoupper($dados_artista['nome'] ?? 'DANIEL KBÇA'); ?></h2>

                <p class="especialidades-artista text-primary"><strong>Especialidades:</strong> <?php echo $especialidades; ?></p>

                <p class="lead text-white-50"><?php echo nl2br($dados_artista['biografia'] ?? 'O artista ainda não preencheu sua biografia.'); ?></p>
            </div>
        </div>

        <hr class="my-5 border-secondary">

        <div class="text-center">
            <h2 class="mb-5">TRABALHOS DO ARTISTA</h2>
            <div class="row">
                <?php if (!empty($trabalhos_vitrine)): ?>
                    <?php foreach ($trabalhos_vitrine as $job): ?>
                        <div class="col-lg-3 col-md-4 col-6 mb-4">
                            <div class="portfolio-item">
                                <img src="../imagens/portfolio/<?php echo $job['imagem']; ?>" alt="<?php echo htmlspecialchars($job['titulo']); ?>">
                                <div class="portfolio-detalhes-overlay">
                                    <h5 class="detalhes-titulo"><?php echo htmlspecialchars($job['titulo']); ?></h5>
                                    <p class="detalhes-info">Estilo: <?php echo $job['estilo_nome']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-white-50">Nenhum trabalho cadastrado no portfólio ainda.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-center mt-4">
                <a href="portfolio.php" class="btn btn-outline-light px-5">VER PORTFÓLIO COMPLETO</a>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<main>
    <div class="container my-5 py-5">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <?php
                $foto = !empty($dados_artista['foto_perfil']) ? "../imagens/perfil/" . $dados_artista['foto_perfil'] : "../imagens/foto_artista.png";
                ?>
                <img src="<?php echo $foto; ?>" class="img-fluid rounded-circle mb-4 foto-perfil-artista" alt="Foto do Artista" style="width: 300px; height: 300px; object-fit: cover;">
            </div>
            <div class="col-md-8">
                <h2 class="display-4"><?php echo strtoupper($dados_artista['nome'] ?? 'DANIEL KBÇA'); ?></h2>

                <p class="especialidades-artista"><strong>Especialidades:</strong> <?php echo $especialidades; ?></p>

                <p><?php echo nl2br($dados_artista['biografia'] ?? 'O artista ainda não preencheu sua biografia.'); ?></p>
            </div>
        </div>

        <hr class="my-5">

        <div class="text-center">
            <h2 class="mb-5">TRABALHOS DO ARTISTA</h2>
            <div class="row">
                <div class="col-lg-3 col-md-4 col-6 mb-4">
                    <div class="portfolio-item">
                        <img src="../imagens/exemplo1.jpg" alt="Trabalho">
                        <div class="portfolio-detalhes-overlay">
                            <h5 class="detalhes-titulo">Dragão</h5>
                            <p class="detalhes-info">Estilo: Oriental</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-4">
                <a href="portfolio.php" class="btn btn-outline-light">VER PORTFÓLIO COMPLETO</a>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>