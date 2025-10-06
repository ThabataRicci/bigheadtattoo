<?php
session_start();
$titulo_pagina = "Login";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <h2 class="text-center mb-4">ACESSAR SUA CONTA</h2>

                <form class="formulario-container" onsubmit="alert('Login realizado com sucesso! (Simulação)'); window.location.href='dashboard-cliente.php'; return false;">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail:</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha:</label>
                        <input type="password" class="form-control" id="senha" required>
                        <div class="text-left mt-3">
                            <a href="recuperar-senha.php" class="text-white-50 small">Esqueci minha senha</a>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-outline-light">ENTRAR</button>
                    </div>

                    <?php
                    # REMOVER A DIV ABAIXO QUANDO ADICIONAR O BANCO DE DADOS, É APENAS SIMULAÇÃO
                    ?>
                    <div class="text-center mt-4 d-flex justify-content-center gap-3">
                        <a href="dashboard-cliente.php" class="btn btn-sm btn-primary">Simular Cliente</a>
                        <a href="dashboard-artista.php" class="btn btn-sm btn-primary">Simular Artista</a>
                    </div>


                    <div class="text-center mt-3">
                        <a href="cadastro.php" class="text-white small">Ainda não tem conta? Cadastre-se</a>
                    </div>
                </form>



            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>