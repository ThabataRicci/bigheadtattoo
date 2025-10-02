<?php
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> | BIG HEAD TATTOO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <header class="p-3">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand logo-cabecalho" href="index.php">
                    <img src="imagens/logo.png" alt="Logo Big Head Tattoo">
                </a>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="container-fluid px-0">
                <div class="collapse navbar-collapse justify-content-center" id="menuPrincipal">
                    <ul class="navbar-nav d-flex justify-content-center gap-4 w-100" style="max-width: 500px; margin: 0 auto;">
                        <li class="nav-item"><a class="nav-link" href="portfolio.php">Portfólio</a></li>
                        <li class="nav-item"><a class="nav-link text-center" href="artista.php">O Artista</a></li>
                        <li class="nav-item"><a class="nav-link" href="agenda.php">Agenda</a></li>
                    </ul>
                </div>
            </div>

            <div class="container">
                <ul class="navbar-nav d-flex flex-row align-items-center position-absolute end-0 me-3">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <?php
                        // Verifica o tipo de usuário e define a URL de configurações
                        if ($_SESSION['user_role'] == 'cliente') {
                            $config_url = 'configuracoes-cliente.php'; // Página de configurações para clientes
                        } elseif ($_SESSION['user_role'] == 'artista') {
                            $config_url = 'configuracoes-artista.php'; // Página de configurações para tatuadores
                        }
                        ?>
                        <li class="nav-item">
                            <a class="nav-link me-2" href="<?php echo $config_url; ?>" title="Configurações">
                                <i class="bi bi-gear-fill fs-5"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Sair</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <?php // LÓGICA DOS SUBMENUS
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true):

        // se for CLIENTE, mostra o submenu do cliente
        if ($_SESSION['user_role'] == 'cliente'): ?>
            <div class="submenu-painel">
                <div class="container d-flex justify-content-center">
                    <a href="dashboard-cliente.php">Painel Inicial</a>
                    <a href="agendamentos-cliente.php">Agendamentos</a>
                </div>
            </div>

        <?php // se for ARTISTA, mostra o submenu do artista
        elseif ($_SESSION['user_role'] == 'artista'): ?>
            <div class="submenu-painel">
                <div class="container d-flex justify-content-center">
                    <a href="dashboard-artista.php">Painel Inicial</a>
                    <a href="agenda-artista.php">Gerenciar Agenda</a>
                    <a href="portfolio-artista.php">Gerenciar Portfólio</a>
                    <a href="relatorios-artista.php">Relatórios</a>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</body>

</html>