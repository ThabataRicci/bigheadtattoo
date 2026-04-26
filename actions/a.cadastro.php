<?php
session_start();
require_once '../includes/conexao.php'; // conexao banco de dados
require_once '../includes/enviar_email.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $telefone_formatado = $_POST['telefone'];
    $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone_formatado);
    $email = trim($_POST['email']);
    $data_nascimento = $_POST['data_nascimento'];
    $senha = $_POST['senha'];
    $confirmar = $_POST['confirmar-senha'];
    $redirect = $_POST['redirect'] ?? '';

    // SALVA OS DADOS TEMPORARIAMENTE CASO DÊ ALGUM ERRO (Exceto senhas)
    $_SESSION['form_backup'] = [
        'nome' => $nome,
        'telefone' => $telefone_formatado,
        'data_nascimento' => $data_nascimento,
        'email' => $email
    ];

    // --- NOVA VALIDAÇÃO DE E-MAIL (DNS) ---
    $dominio = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($dominio, "MX")) {
        header("Location: ../pages/cadastro.php?erro=email_invalido&redirect=" . urlencode($redirect));
        exit();
    }

    if ($senha !== $confirmar) {
        header("Location: ../pages/cadastro.php?erro=senha&redirect=" . urlencode($redirect));
        exit();
    }

    if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/', $senha)) {
        header("Location: ../pages/cadastro.php?erro=senha_fraca&redirect=" . urlencode($redirect));
        exit();
    }

    $sql_busca_tel = "SELECT id_usuario FROM usuario WHERE telefone = ?";
    $stmt_busca_tel = $pdo->prepare($sql_busca_tel);
    $stmt_busca_tel->execute([$telefone_limpo]);

    if ($stmt_busca_tel->fetch()) {
        header("Location: ../pages/cadastro.php?erro=telefone&redirect=" . urlencode($redirect));
        exit();
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        // --- CALCULAR IDADE E DEFINIR STATUS ---
        $dataNascObjeto = new DateTime($data_nascimento);
        $hoje = new DateTime('today');
        $idade = $dataNascObjeto->diff($hoje)->y;

        $status_inicial = ($idade < 18) ? 'Bloqueado' : 'Ativo';

        // --- INSERIR NO BANCO ---
        $sql = "INSERT INTO usuario (nome, telefone, email, senha, perfil, data_nascimento, status, data_cadastro) VALUES (?, ?, ?, ?, 'cliente', ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $telefone_limpo, $email, $senha_hash, $data_nascimento, $status_inicial]);

        $id_novo_usuario = $pdo->lastInsertId();

        unset($_SESSION['form_backup']);

        // --- LÓGICA DE DIRECIONAMENTO COM BASE NA IDADE ---
        if ($idade < 18) {
            // Menor de idade: NÃO loga, NÃO manda e-mail, manda pro login com aviso de bloqueio
            header("Location: ../pages/login.php?aviso=menor_idade");
            exit();
        } else {
            // Maior de idade: Loga normalmente e manda e-mail
            $_SESSION['usuario_id'] = $id_novo_usuario;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_perfil'] = 'cliente';
            $_SESSION['loggedin'] = true;

            // ================= E-MAIL DE BOAS-VINDAS =================
            $link = "https://" . $_SERVER['HTTP_HOST'] . "/pages/login.php";
            $primeiro_nome = explode(" ", $nome)[0];

            $msg = "
<div style='font-family: Arial, sans-serif; background-color: #000000; color: #f8f9fa; padding: 40px 20px; border-radius: 10px; max-width: 600px; margin: 0 auto; border: 1px solid #333;'>
    <div style='text-align: center; margin-bottom: 30px;'><h1 style='color: #ffffff; margin: 0; letter-spacing: 2px;'>BIG HEAD TATTOO</h1></div>
    <div style='background-color: #212529; padding: 30px; border-radius: 8px; border-top: 4px solid #ffffff;'>
        <h2 style='color: #ffffff; margin-top: 0; font-size: 20px;'>BEM-VINDO(A) AO ESTÚDIO! 💉</h2>
        <p style='font-size: 16px; color: #cccccc;'>Olá, <strong>{$primeiro_nome}</strong>!</p>
        <p style='font-size: 15px; color: #cccccc;'>Sua conta foi criada com sucesso no sistema do Big Head Tattoo. Estamos muito felizes em ter você com a gente!</p>
        
        <div style='background-color: #1a1d20; padding: 20px; border-radius: 5px; border-left: 4px solid #ffffff; margin: 25px 0;'>
            <h3 style='color: #ffffff; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 15px 0;'>O que você pode fazer agora:</h3>
            <ul style='color: #cccccc; margin: 0; padding-left: 20px; line-height: 1.6; font-size: 14px;'>
                <li>Enviar ideias e pedir orçamentos sem compromisso.</li>
                <li>Acompanhar o status das suas propostas em tempo real.</li>
                <li>Agendar e gerenciar os horários das suas sessões.</li>
                <li>Ver o histórico de todas as suas tatuagens feitas com a gente.</li>
            </ul>
        </div>

        <p style='font-size: 14px; color: #aaa; text-align: center; margin-top: 30px;'>
            Pronto para riscar a pele?
        </p>

        <div style='text-align: center; margin-top: 30px; margin-bottom: 10px;'>
            <a href='{$link}' style='background-color: #ffffff; color: #000; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold;'>ACESSAR MEU PAINEL</a>
        </div>
    </div>
</div>";
            dispararEmail($email, $nome, "Bem-vindo(a) ao Big Head Tattoo!", $msg);
            // =========================================================

            if ($redirect === 'solicitar-orcamento.php') {
                header("Location: ../pages/solicitar-orcamento.php");
            } else {
                header("Location: ../pages/dashboard-cliente.php");
            }
            exit();
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            header("Location: ../pages/cadastro.php?erro=email&redirect=" . urlencode($redirect));
            exit();
        }
        die("Erro ao cadastrar: " . $e->getMessage());
    }
} else {
    header("Location: ../pages/cadastro.php");
    exit();
}
