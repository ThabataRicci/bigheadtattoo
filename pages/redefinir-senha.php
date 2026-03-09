<?php
session_start();
$titulo_pagina = "Redefinir Senha";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <h2 class="text-center mb-4">CRIE SUA NOVA SENHA</h2>

                <form class="formulario-container" action="../actions/a.redefinir-senha.php" method="POST">
    <div class="mb-3">
        <label for="nova-senha" class="form-label">Nova Senha:</label>
        <input type="password" class="form-control" name="nova_senha" id="nova-senha" required>
    </div>

    <div class="mb-4">
        <label for="confirmar-nova-senha" class="form-label">Confirmar Nova Senha:</label>
        <input type="password" class="form-control" name="confirmar_senha" id="confirmar-nova-senha" required>
    </div>
    
    <input type="hidden" name="usuario_id" value="<?php echo $_GET['id'] ?? ''; ?>">

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-outline-light">SALVAR NOVA SENHA</button>
    </div>
</form>

            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>