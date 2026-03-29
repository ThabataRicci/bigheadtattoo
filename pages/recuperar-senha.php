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

                <?php if (isset($_GET['erro']) && $_GET['erro'] == 'email_nao_encontrado'): ?>
                    <div class="alert alert-danger text-center">E-mail não encontrado no sistema.</div>
                <?php endif; ?>

                <form class="formulario-container" action="../actions/a.recuperar-senha.php" method="POST">
                    <div class="mb-4">
                        <label for="email" class="form-label">E-mail cadastrado:</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-outline-light">ENVIAR LINK DE RECUPERAÇÃO</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>