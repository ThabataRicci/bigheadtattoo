<?php
require_once '../includes/conexao.php';

// 1. Configuração de paginação
$itens_por_pagina = 12;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// 2. Captura de Filtros
$titulo_filtro = $_GET['titulo'] ?? '';
$estilo_filtro = $_GET['estilo'] ?? 'todos';
$local_filtro  = $_GET['local_corpo'] ?? '';
$sessoes_filtro = $_GET['qtd_sessoes'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$ordem = $_GET['ordem'] ?? 'desc';

// 3. Consulta de estilos (para o select)
$lista_estilos = $pdo->query("SELECT * FROM estilo ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// 4. Construção da Query Principal
$sql_port = "SELECT p.*, e.nome as estilo_nome 
             FROM portfolio p 
             INNER JOIN estilo e ON p.id_estilo = e.id_estilo 
             WHERE 1=1";

$params = [];

if (!empty($titulo_filtro)) {
    $sql_port .= " AND p.titulo LIKE :titulo";
    $params[':titulo'] = "%$titulo_filtro%";
}
if ($estilo_filtro !== 'todos') {
    $sql_port .= " AND e.nome = :estilo";
    $params[':estilo'] = $estilo_filtro;
}
if (!empty($local_filtro)) {
    $sql_port .= " AND p.local_corpo LIKE :local";
    $params[':local'] = "%$local_filtro%";
}
if (!empty($sessoes_filtro)) {
    $sql_port .= " AND p.qtd_sessoes = :sessoes";
    $params[':sessoes'] = $sessoes_filtro;
}
if (!empty($data_inicio)) {
    $sql_port .= " AND DATE(p.data_publicacao) >= :inicio";
    $params[':inicio'] = $data_inicio;
}
if (!empty($data_fim)) {
    $sql_port .= " AND DATE(p.data_publicacao) <= :fim";
    $params[':fim'] = $data_fim;
}

$sql_port .= ($ordem === 'asc') ? " ORDER BY p.data_publicacao ASC" : " ORDER BY p.data_publicacao DESC";

$sql_port .= " LIMIT $itens_por_pagina OFFSET $offset";

$stmt = $pdo->prepare($sql_port);
$stmt->execute($params);
$trabalhos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql_total = "SELECT COUNT(*) FROM portfolio p INNER JOIN estilo e ON p.id_estilo = e.id_estilo WHERE 1=1";

$stmt_total = $pdo->prepare(str_replace("SELECT p.*, e.nome as estilo_nome", "SELECT COUNT(*)", explode(" LIMIT", $sql_port)[0]));

$stmt_total->execute($params);

$total_registros = $stmt_total->fetchColumn();
$total_paginas = ceil($total_registros / $itens_por_pagina);
