<?php
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> | BIG HEAD TATTOO</title>

    <?php
    // Define o caminho base para os assets (CSS, imagens)
    $base_path = (strpos($_SERVER['REQUEST_URI'], '/pages/') === false) ? '' : '../';
    ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/style.css">
</head>

<body>

    <header class="p-3">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand logo-cabecalho" href="<?php echo $base_path; ?>index.php">
                    <img src="<?php echo $base_path; ?>imagens/logo.png" alt="Logo Big Head Tattoo">
                </a>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="container-fluid px-0">
                <div class="collapse navbar-collapse justify-content-center" id="menuPrincipal">
                    <ul class="navbar-nav d-flex justify-content-center gap-4 w-100" style="max-width: 500px; margin: 0 auto;">
                        <?php
                        $pages_prefix = (strpos($_SERVER['REQUEST_URI'], '/pages/') === false) ? 'pages/' : '';
                        ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $pages_prefix; ?>portfolio.php">Portfólio</a></li>
                        <li class="nav-item"><a class="nav-link text-center" href="<?php echo $pages_prefix; ?>artista.php">O Artista</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $pages_prefix; ?>agenda.php">Agenda</a></li>
                    </ul>
                </div>
            </div>

            <div class="container">
                <ul class="navbar-nav d-flex flex-row align-items-center position-absolute end-0 me-3">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <?php
                        // Define a URL do dashboard e das configurações com base no tipo de usuário
                        if ($_SESSION['user_role'] == 'cliente') {
                            $dashboard_url = 'dashboard-cliente.php';
                            $config_url = 'configuracoes-cliente.php';
                        } elseif ($_SESSION['user_role'] == 'artista') {
                            $dashboard_url = 'dashboard-artista.php';
                            $config_url = 'configuracoes-artista.php';
                        }
                        ?>
                        <li class="nav-item">
                            <a class="nav-link me-2" href="<?php echo $dashboard_url; ?>" title="Painel Inicial">
                                <i class="bi bi-house-door-fill fs-5"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link me-2" href="<?php echo $config_url; ?>" title="Configurações">
                                <i class="bi bi-gear-fill fs-5"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/bigheadtattoo/pages/logout.php">Sair</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/bigheadtattoo/pages/login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
</body>

</html>