<?php
session_start();
require_once '../includes/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['usuario_id'];

// busca os dados do cliente no BD
$stmt = $pdo->prepare("SELECT nome, email, telefone FROM usuario WHERE id_usuario = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

$titulo_pagina = "Minhas Configurações";
include '../includes/header.php';
?>

<?php
if (isset($_SESSION['usuario_id'])) {
    $pagina_ativa = basename($_SERVER['PHP_SELF']);
    echo '<div class="submenu-painel">';
    echo '<a href="dashboard-cliente.php" class="' . ($pagina_ativa == 'dashboard-cliente.php' ? 'active' : '') . '">Início</a>';
    echo '<a href="agendamentos-cliente.php">Meus Agendamentos</a>';
    echo '<a href="solicitar-orcamento.php">Orçamento</a>';
    echo '<a href="configuracoes-cliente.php" class="active">Configurações</a>';
    echo '</div>';
}
?>

<main>
    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">

                <h2 class="text-center mb-5">EDITAR MEU PERFIL</h2>

                <?php if (isset($_GET['sucesso'])): ?>
                    <div class="alert alert-success text-center mb-4">
                        <?php
                        echo ($_GET['sucesso'] == 'senha') ? "Senha atualizada com sucesso!" : "Dados atualizados com sucesso!";
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['erro'])): ?>
                    <div class="alert alert-danger text-center mb-4">
                        <?php
                        if ($_GET['erro'] == 'senha_atual') echo "Erro: A senha atual digitada está incorreta.";
                        elseif ($_GET['erro'] == 'confirmacao') echo "Erro: A nova senha e a confirmação não coincidem.";
                        elseif ($_GET['erro'] == 'senha_fraca') echo "Erro: A nova senha deve ter 8+ caracteres, maiúscula e número.";
                        elseif ($_GET['erro'] == 'senha_igual') echo "Erro: A nova senha não pode ser igual à senha atual.";
                        elseif ($_GET['erro'] == 'email_duplicado') echo "Erro: Este e-mail já está em uso.";
                        elseif ($_GET['erro'] == 'senha_excluir') echo "Erro: Senha incorreta. A conta não foi excluída.";
                        elseif ($_GET['erro'] == 'excluir') echo "Erro técnico ao tentar excluir a conta. Contate o suporte.";
                        else echo "Erro ao processar as alterações.";
                        ?>
                    </div>
                <?php endif; ?>

                <form class="formulario-container" action="../actions/a.configuracoes-cliente.php" method="POST">

                    <ul class="nav nav-tabs nav-tabs-dark mb-4" id="abasConfigCliente" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#tab-dados" type="button" role="tab">Dados Pessoais</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="senha-tab" data-bs-toggle="tab" data-bs-target="#tab-senha" type="button" role="tab">Alterar Senha</button>
                        </li>
                    </ul>

                    <div class="tab-content tab-content-boxed" id="abasConfigClienteConteudo">

                        <div class="tab-pane fade show active" id="tab-dados" role="tabpanel">
                            <h5 class="text-white-50 mb-3">DADOS PESSOAIS</h5>

                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo:</label>
                                <input type="text" class="form-control" name="nome" value="<?php echo $user['nome']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail:</label>
                                <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="telefone" class="form-label">Telefone:</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" value="<?php echo $user['telefone']; ?>" required>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-senha" role="tabpanel">

                            <div class="mb-3">
                                <label for="senha-atual" class="form-label">Senha Atual:</label>
                                <input type="password" class="form-control" name="senha_atual" id="senha-atual">
                            </div>

                            <hr class="border-secondary my-4">

                            <div class="mb-3">
                                <label for="nova-senha" class="form-label">Nova Senha:</label>
                                <input type="password" class="form-control" name="nova_senha" id="nova-senha">
                                <div id="senha-aviso" class="text-warning small mt-1" style="display: none;">
                                    Mínimo 8 caracteres, 1 maiúscula e 1 número.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirmar-nova-senha" class="form-label">Confirmar Nova Senha:</label>
                                <input type="password" class="form-control" name="confirmar_senha" id="confirmar-nova-senha">
                                <div id="confirmar-aviso" class="text-danger small mt-1" style="display: none;">
                                    As senhas não coincidem.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">SALVAR ALTERAÇÕES</button>
                    </div>
                </form>

                <div class="mt-5 pt-4 border-top border-secondary text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluirConta">
                        EXCLUIR MINHA CONTA
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // configuracao telefone
    const tel = document.getElementById('telefone');
    tel.addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, "");
        if (v.length > 11) v = v.slice(0, 11);
        if (v.length > 0) {
            v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
            v = v.replace(/(\d{5})(\d)/, "$1-$2");
        }
        e.target.value = v;
    });

    // validar senha em tempo real
    const novaSenha = document.getElementById('nova-senha');
    const confirmarSenha = document.getElementById('confirmar-nova-senha');
    const senhaAviso = document.getElementById('senha-aviso');
    const confirmarAviso = document.getElementById('confirmar-aviso');

    novaSenha.addEventListener('input', () => {
        const regex = /^(?=.*[A-Z])(?=.*[0-9]).{8,}$/;
        senhaAviso.style.display = (novaSenha.value.length > 0 && !regex.test(novaSenha.value)) ? 'block' : 'none';
    });

    confirmarSenha.addEventListener('input', () => {
        confirmarAviso.style.display = (confirmarSenha.value.length > 0 && confirmarSenha.value !== novaSenha.value) ? 'block' : 'none';
    });
</script>

<div class="modal fade" id="modalExcluirConta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light bg-dark border-secondary">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-danger">Atenção! Ação Irreversível</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-white-50">
                <p>Você tem certeza que deseja excluir sua conta? Todos os seus dados, histórico de sessões e projetos serão <strong>apagados permanentemente</strong>.</p>
                <p class="small">Para confirmar, digite sua <strong>senha atual</strong> abaixo:</p>

                <form action="../actions/a.excluir-conta.php" method="POST">
                    <div class="mb-3">
                        <input type="password" class="form-control bg-dark text-light border-secondary" name="senha_confirmacao" required placeholder="Sua senha">
                    </div>
                    <div class="modal-footer border-top border-secondary p-0 pt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>