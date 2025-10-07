<?php
session_start();

// 1. VERIFICA SE O CLIENTE ESTÁ LOGADO
// Se não estiver logado, redireciona para a página de login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Garante que apenas clientes possam solicitar (artistas não podem)
if ($_SESSION['user_role'] === 'artista') {
    // Redireciona o artista para o painel dele, pois ele não solicita orçamento
    header("location: dashboard-artista.php");
    exit;
}

$titulo_pagina = "Solicitar Orçamento";
include '../includes/header.php';
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">

                <form class="formulario-container text-center" action="processa_solicitacao.php" method="POST" enctype="multipart/form-data">

                    <h2 class="mb-3">SOLICITAR ORÇAMENTO</h2>
                    <p class="text-white-50 mb-4">Descreva sua tatuagem e envie referências. O artista irá analisar o projeto e você receberá a notificação para agendar sua sessão.</p>

                    <div class="text-start">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="local_corpo" class="form-label">Local do Corpo:</label>
                                <input type="text" class="form-control" id="local_corpo" name="local_corpo" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tamanho_aproximado" class="form-label">Tamanho Aproximado:</label>
                                <select class="form-select" id="tamanho_aproximado" name="tamanho_aproximado" required>
                                    <option value="" selected disabled>Selecione o tamanho...</option>
                                    <option value="Pequeno (até 10cm)">Pequeno (até 10cm)</option>
                                    <option value="Médio (11cm a 20cm)">Médio (11cm a 20cm)</option>
                                    <option value="Grande (acima de 20cm)">Grande (acima de 20cm)</option>
                                    <option value="Fechamento">Fechamento (braço, perna, etc.)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descricao_ideia" class="form-label">Detalhes da Ideia / Desenho:</label>
                            <textarea class="form-control" id="descricao_ideia" name="descricao_ideia" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="referencia_ideia" class="form-label">Imagem de Referência (Opcional):</label>
                            <input type="file" class="form-control" id="referencia_ideia" name="referencia_ideia">
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary">ENVIAR IDEIA PARA ANÁLISE</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</main>

<?php
include '../includes/footer.php';
?>