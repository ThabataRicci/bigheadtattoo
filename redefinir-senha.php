<?php
session_start();
$titulo_pagina = "Redefinir Senha";
include 'includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <h2 class="text-center mb-4">CRIE SUA NOVA SENHA</h2>

                <form class="formulario-container">
                    <div class="mb-3">
                        <label for="nova-senha" class="form-label">Nova Senha:</label>
                        <input type="password" class="form-control" id="nova-senha" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirmar-nova-senha" class="form-label">Confirmar Nova Senha:</label>
                        <input type="password" class="form-control" id="confirmar-nova-senha" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-outline-light">SALVAR NOVA SENHA</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?>