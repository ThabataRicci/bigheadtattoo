<?php
session_start();

// Simula o login do artista para teste
$_SESSION['loggedin'] = true;
$_SESSION['user_role'] = "artista";

$titulo_pagina = "Configurações";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">

                <h2 class="text-center mb-5">EDITAR PERFIL</h2>

                <form class="formulario-container">
                    <h5 class="text-white-50 mb-3">DADOS DO PERFIL</h5>

                    <div class="mb-3">
                        <label for="foto-perfil" class="form-label">Foto de Perfil:</label>
                        <input type="file" class="form-control" id="foto-perfil">
                    </div>
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome:</label>
                        <input type="text" class="form-control" id="nome" value="DANIEL KBÇA">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Especialidades (selecione um ou mais):</label>
                        <div class="p-3" style="background-color: #2c2c2c; border-radius: 8px;">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="esp_maori" value="maori" checked>
                                <label class="form-check-label" for="esp_maori">Maori</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="esp_geometrico" value="geometrico" checked>
                                <label class="form-check-label" for="esp_geometrico">Geométrico</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="esp_escrita" value="escrita" checked>
                                <label class="form-check-label" for="esp_escrita">Escrita</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="esp_fineline" value="fineline">
                                <label class="form-check-label" for="esp_fineline">Fineline</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="esp_oriental" value="oriental">
                                <label class="form-check-label" for="esp_oriental">Oriental</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="bio" class="form-label">Biografia:</label>
                        <textarea class="form-control" id="bio" rows="4">Apaixonado por transformar ideias em arte na pele, Daniel Kbça construiu sua trajetória unindo técnica, criatividade e atenção aos detalhes...</textarea>
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