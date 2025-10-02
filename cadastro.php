<?php
session_start();
$titulo_pagina = "Cadastro";
include 'includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <h2 class="text-center mb-4">CRIE SUA CONTA</h2>

                <form class="formulario-container">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo:</label>
                        <input type="text" class="form-control" id="nome" required>
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone:</label>
                        <input type="tel" class="form-control" id="telefone" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail:</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha:</label>
                        <input type="password" class="form-control" id="senha" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirmar-senha" class="form-label">Confirmar Senha:</label>
                        <input type="password" class="form-control" id="confirmar-senha" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-outline-light">CADASTRAR</button>
                    </div>

                    <div class="text-center mt-3">
                        <a href="login.php" class="text-white small">Já tem uma conta? Faça o login</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?>