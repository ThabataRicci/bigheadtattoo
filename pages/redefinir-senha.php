<?php
session_start();
require_once '../includes/conexao.php';

$token = $_GET['token'] ?? '';
$token_valido = false;

// Verifica se o token veio na URL e se é válido/não expirou
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE token_recuperacao = ? AND expiracao_token > NOW()");
    $stmt->execute([$token]);
    if ($stmt->rowCount() > 0) {
        $token_valido = true;
    }
}

$titulo_pagina = "Redefinir Senha";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <h2 class="text-center mb-4">CRIE SUA NOVA SENHA</h2>

                <?php if (isset($_GET['erro']) && $_GET['erro'] == 'senhas_diferentes'): ?>
                    <div class="alert alert-danger text-center">As senhas não coincidem. Tente novamente.</div>
                <?php endif; ?>

                <?php if ($token_valido): ?>
                    <form class="formulario-container" action="../actions/a.redefinir-senha.php" method="POST">
                        <div class="mb-3">
                            <label for="nova-senha" class="form-label">Nova Senha:</label>
                            <input type="password" class="form-control" name="nova_senha" id="nova-senha" required>
                        </div>
                        <div class="mb-4">
                            <label for="confirmar-nova-senha" class="form-label">Confirmar Nova Senha:</label>
                            <input type="password" class="form-control" name="confirmar_senha" id="confirmar-nova-senha" required>
                        </div>

                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-outline-light">SALVAR NOVA SENHA</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger text-center">
                        <strong>Link inválido ou expirado!</strong><br>
                        Por motivos de segurança, os links de recuperação duram apenas 1 hora.
                    </div>
                    <div class="d-grid mt-4">
                        <a href="recuperar-senha.php" class="btn btn-outline-light">SOLICITAR NOVO LINK</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>