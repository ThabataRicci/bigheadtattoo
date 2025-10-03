<?php
session_start();
$titulo_pagina = "Recuperar Senha";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <h2 class="text-center mb-4">RECUPERAR SENHA</h2>
                <p class="text-center text-white-50 mb-4">Digite seu e-mail abaixo e enviaremos um link para você criar uma nova senha.</p>

                <form class="formulario-container">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail:</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-outline-light">ENVIAR LINK DE RECUPERAÇÃO</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>