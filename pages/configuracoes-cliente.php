<?php
session_start();
$titulo_pagina = "Configurações";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">

                <h2 class="text-center mb-5">EDITAR MEU PERFIL</h2>

                <form class="formulario-container">
                    <h5 class="text-white-50 mb-3">DADOS PESSOAIS</h5>

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo:</label>
                        <input type="text" class="form-control" id="nome" value="[NOME ATUAL DO CLIENTE]">
                    </div>

                    <div class="mb-4">
                        <label for="telefone" class="form-label">Telefone:</label>
                        <input type="tel" class="form-control" id="telefone" value="[TELEFONE ATUAL]">
                    </div>

                    <hr class="my-4">

                    <h5 class="text-white-50 mb-3">ALTERAR SENHA</h5>

                    <div class="mb-3">
                        <label for="senha-atual" class="form-label">Senha Atual:</label>
                        <input type="password" class="form-control" id="senha-atual">
                    </div>

                    <div class="mb-3">
                        <label for="nova-senha" class="form-label">Nova Senha:</label>
                        <input type="password" class="form-control" id="nova-senha">
                    </div>

                    <div class="mb-4">
                        <label for="confirmar-nova-senha" class="form-label">Confirmar Nova Senha:</label>
                        <input type="password" class="form-control" id="confirmar-nova-senha">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>