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

                <?php
                // exibe mensagem de erro
                if (isset($_GET['erro'])) {
                    if ($_GET['erro'] == 'bloqueado') {
                        echo '<div class="alert alert-warning text-center">Sua conta foi bloqueada. Entre em contato com o artista para mais detalhes.</div>';
                    } else {
                        echo '<div class="alert alert-danger text-center">E-mail ou senha incorretos. Tente novamente.</div>';
                    }
                }

                // exibe mensagens de SUCESSO dinâmicas
                if (isset($_GET['sucesso'])) {
                    if ($_GET['sucesso'] == 'conta_excluida') {
                        echo '<div class="alert alert-warning text-center alert-dismissible fade show" role="alert">
                                <i class="bi bi-info-circle me-2"></i> Sua conta e seus dados foram excluídos com sucesso.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                    } elseif ($_GET['sucesso'] == 'recuperacao_enviada') {
                        echo '<div class="alert alert-success text-center alert-dismissible fade show" role="alert">
                                <i class="bi bi-envelope-check me-2"></i> E-mail de recuperação enviado! Verifique sua caixa de entrada (e a pasta de SPAM).
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                    } elseif ($_GET['sucesso'] == 'senha_redefinida') {
                        echo '<div class="alert alert-success text-center alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i> Sua senha foi redefinida com sucesso! Faça login com a nova senha.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                    }
                }
                ?>

                <form class="formulario-container" action="../actions/a.login.php" method="POST">

                    <input type="hidden" name="redirect" value="<?php echo $_GET['redirect'] ?? ''; ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail:</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha:</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="senha" name="senha" required>
                            <button class="btn btn-outline-light" type="button" id="toggle-senha">
                                <i class="bi bi-eye" id="icone-senha"></i>
                            </button>
                        </div>
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

<script>
    // mostrar ou esconder senha
    const btnToggle = document.getElementById('toggle-senha');
    const inputSenha = document.getElementById('senha');
    const icone = document.getElementById('icone-senha');

    btnToggle.addEventListener('click', () => {
        const tipo = inputSenha.getAttribute('type') === 'password' ? 'text' : 'password';
        inputSenha.setAttribute('type', tipo);
        icone.classList.toggle('bi-eye');
        icone.classList.toggle('bi-eye-slash');
    });
</script>

<?php
include '../includes/footer.php';
?>