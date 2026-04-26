<?php
session_start();
$titulo_pagina = "Cadastro";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <h2 class="text-center mb-4">CRIE SUA CONTA</h2>

                <?php
                if (isset($_GET['erro'])) {
                    if ($_GET['erro'] == 'senha') {
                        echo '<div class="alert alert-danger text-center">As senhas não coincidem.</div>';
                    } elseif ($_GET['erro'] == 'email') {
                        echo '<div class="alert alert-danger text-center">Este e-mail já está cadastrado.</div>';
                    } elseif ($_GET['erro'] == 'telefone') {
                        echo '<div class="alert alert-danger text-center">Este telefone já está cadastrado.</div>';
                    } elseif ($_GET['erro'] == 'duplicado') {
                        echo '<div class="alert alert-danger text-center">E-mail ou telefone já em uso.</div>';
                    } elseif ($_GET['erro'] == 'senha_fraca') {
                        echo '<div class="alert alert-danger text-center">A senha deve ter no mínimo 8 caracteres, pelo menos uma letra maiúscula e um número.</div>';
                    } elseif ($_GET['erro'] == 'email_invalido') {
                        echo '<div class="alert alert-danger text-center">O domínio do e-mail informado não parece ser válido. Verifique se digitou corretamente.</div>';
                    }
                }

                // Resgata os dados salvos caso tenha ocorrido um erro e limpa a sessão
                $backup = $_SESSION['form_backup'] ?? [];
                unset($_SESSION['form_backup']);
                ?>

                <form class="formulario-container" action="../actions/a.cadastro.php" method="POST">

                    <input type="hidden" name="redirect" value="<?php echo $_GET['redirect'] ?? ''; ?>">

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo:</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($backup['nome'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone:</label>
                        <input type="tel" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($backup['telefone'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="data_nascimento" class="form-label text-light">Data de Nascimento</label>
                        <input type="date" class="form-control bg-dark text-light border-secondary"
                            id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($backup['data_nascimento'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($backup['email'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha:</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="senha" name="senha" required>
                            <button class="btn btn-outline-light" type="button" onclick="togglePassword('senha', 'icone-1')">
                                <i class="bi bi-eye" id="icone-1"></i>
                            </button>
                        </div>
                        <div id="senha-ajuda" class="text-warning small mt-1" style="display: none;">
                            A senha deve ter pelo menos 8 caracteres, uma letra maiúscula e um número.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirmar-senha" class="form-label">Confirmar Senha:</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmar-senha" name="confirmar-senha" required>
                            <button class="btn btn-outline-light" type="button" onclick="togglePassword('confirmar-senha', 'icone-2')">
                                <i class="bi bi-eye" id="icone-2"></i>
                            </button>
                        </div>
                        <div id="confirmar-aviso" class="text-danger small mt-1" style="display: none;">
                            As senhas não coincidem.
                        </div>
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

<script>
    // senha
    const senhaInput = document.getElementById('senha');
    const confirmarInput = document.getElementById('confirmar-senha');
    const senhaAjuda = document.getElementById('senha-ajuda');
    const confirmarAviso = document.getElementById('confirmar-aviso');

    //  validar se as duas senhas digitadas sao iguais
    function validarMatch() {
        if (confirmarInput.value.length > 0 && confirmarInput.value !== senhaInput.value) {
            confirmarAviso.style.display = 'block';
        } else {
            confirmarAviso.style.display = 'none';
        }
    }

    // validação de força (minimo 8 caracteres, 1 maiúscula, 1 número)
    senhaInput.addEventListener('input', () => {
        const regex = /^(?=.*[A-Z])(?=.*[0-9]).{8,}$/;

        if (senhaInput.value.length > 0 && !regex.test(senhaInput.value)) {
            senhaAjuda.style.display = 'block';
        } else {
            senhaAjuda.style.display = 'none';
        }

        validarMatch();
    });

    confirmarInput.addEventListener('input', validarMatch);


    // telefone
    const tel = document.getElementById('telefone');
    tel.addEventListener('input', (e) => {
        let valor = e.target.value.replace(/\D/g, ""); // remove o que não é número

        // limitar a 11 números
        if (valor.length > 11) {
            valor = valor.slice(0, 11);
        }

        // aplica a formatação (XX) XXXXX-XXXX
        if (valor.length > 0) {
            valor = valor.replace(/^(\d{2})(\d)/g, "($1) $2");
            valor = valor.replace(/(\d{5})(\d)/, "$1-$2");
        }

        e.target.value = valor;
    });

    // mostrar/esconder senha
    function togglePassword(inputId, iconeId) {
        const input = document.getElementById(inputId);
        const icone = document.getElementById(iconeId);

        const tipo = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', tipo);

        icone.classList.toggle('bi-eye');
        icone.classList.toggle('bi-eye-slash');
    }
</script>

<?php

include '../includes/footer.php';
?>