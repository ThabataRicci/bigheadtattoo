<?php
session_start();
$titulo_pagina = "Login";
include 'includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <h2 class="text-center mb-4">ACESSAR SUA CONTA</h2>

                <form class="formulario-container">
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

                    <div class="text-center mt-3">
                        <a href="cadastro.php" class="text-white small">Ainda não tem conta? Cadastre-se</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?>