<?php
require_once '../includes/conexao.php';

$tipo = $_GET['tipo'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id === 0) exit();

if ($tipo === 'projetos_cliente') {
    // Busca os dados do cliente para repassar ao modal do projeto
    $stmt_cli = $pdo->prepare("SELECT id_usuario, nome, email, telefone, data_cadastro, status,
                                (SELECT COUNT(*) FROM sessao s JOIN projeto p ON s.id_projeto = p.id_projeto WHERE p.id_usuario = u.id_usuario AND s.status = 'Concluído') as qtd_sessoes_total,
                                (SELECT SUM(s3.valor_sessao) FROM sessao s3 JOIN projeto p3 ON s3.id_projeto = p3.id_projeto WHERE p3.id_usuario = u.id_usuario AND s3.status = 'Concluído') as total_gasto_cliente
                                FROM usuario u WHERE id_usuario = (SELECT id_usuario FROM usuario WHERE id_usuario = ? LIMIT 1)");
    $stmt_cli->execute([$id]);
    $cli = $stmt_cli->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT p.id_projeto, p.titulo, p.status, o.local_corpo, o.descricao_ideia, o.estimativa_tempo, o.qtd_sessoes 
                           FROM projeto p 
                           LEFT JOIN orcamento o ON p.id_orcamento = o.id_orcamento 
                           WHERE p.id_usuario = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$res) echo "<div class='text-white-50 small'>Nenhum projeto encontrado.</div>";

    foreach ($res as $p) {
        $badge = ($p['status'] == 'Finalizado') ? 'bg-success' : 'bg-primary';
        $titulo_esc = htmlspecialchars($p['titulo'], ENT_QUOTES);

        echo "<div class='d-flex justify-content-between align-items-center mb-2 border-bottom border-dark pb-2'>
                <a href='#' class='text-light text-decoration-none btn-detalhes-projeto-interno' 
                   data-id-proj='{$p['id_projeto']}' 
                   data-titulo='{$titulo_esc}' 
                   data-local='" . htmlspecialchars($p['local_corpo'] ?? 'Não informado', ENT_QUOTES) . "' 
                   data-duracao='" . htmlspecialchars($p['estimativa_tempo'] ?? 'A definir', ENT_QUOTES) . "' 
                   data-sessoes='" . htmlspecialchars($p['qtd_sessoes'] ?? '-', ENT_QUOTES) . "' 
                   data-ideia='" . htmlspecialchars($p['descricao_ideia'] ?? 'Não informada', ENT_QUOTES) . "'
                   data-cli-id='{$cli['id_usuario']}'
                   data-cli-nome='" . htmlspecialchars($cli['nome'], ENT_QUOTES) . "'
                   data-cli-email='" . htmlspecialchars($cli['email'], ENT_QUOTES) . "'
                   data-cli-telefone='" . htmlspecialchars($cli['telefone'], ENT_QUOTES) . "'
                   data-cli-cadastro='" . date('d/m/Y', strtotime($cli['data_cadastro'])) . "'
                   data-cli-sessoes='{$cli['qtd_sessoes_total']}'
                   data-cli-gasto='" . number_format($cli['total_gasto_cliente'] ?? 0, 2, ',', '.') . "'
                   data-cli-status='{$cli['status']}'
                   data-bs-toggle='modal' data-bs-target='#modalDetalhesProjeto'>
                   {$titulo_esc} <i class='bi bi-search ms-1 icone-lupa'></i>
                </a>
                <span class='badge {$badge}'>{$p['status']}</span>
              </div>";
    }
}

if ($tipo === 'historico_projeto') {
    $stmt = $pdo->prepare("SELECT data_hora, status, valor_sessao FROM sessao WHERE id_projeto = ? ORDER BY data_hora ASC");
    $stmt->execute([$id]);
    $sessoes = $stmt->fetchAll();

    if (!$sessoes) echo "<div class='text-white-50 small'>Nenhuma sessão registrada.</div>";

    $contador = 1;
    foreach ($sessoes as $s) {
        $d = date('d/m/Y', strtotime($s['data_hora']));
        $v = !empty($s['valor_sessao']) ? " - R$ " . number_format($s['valor_sessao'], 2, ',', '.') : "";
        echo "<div><strong class='text-light'>{$contador}ª Sessão:</strong> {$d} | Status: {$s['status']}{$v}</div><hr class='border-secondary my-2'>";
        $contador++;
    }
}
