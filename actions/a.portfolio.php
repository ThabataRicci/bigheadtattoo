<?php
require_once '../includes/conexao.php';

// 1. configuração de paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// 2. filtro de estilo
$estilo_filtro = isset($_GET['estilo']) ? $_GET['estilo'] : 'todos';

// 3. consulta de estilos
$sql_estilos = "SELECT * FROM estilo ORDER BY nome ASC";
$query_estilos = $pdo->query($sql_estilos);
$lista_estilos = $query_estilos->fetchAll(PDO::FETCH_ASSOC);

// 4. consulta do Portfólio com poin p pegar o nome do estilo
$sql_port = "SELECT p.*, e.nome as estilo_nome 
             FROM portfolio p 
             INNER JOIN estilo e ON p.id_estilo = e.id_estilo";

$params = [];
if ($estilo_filtro !== 'todos') {
    $sql_port .= " WHERE e.nome = :estilo";
    $params[':estilo'] = $estilo_filtro;
}

$sql_port .= " ORDER BY p.data_publicacao DESC LIMIT $itens_por_pagina OFFSET $offset";

$stmt = $pdo->prepare($sql_port);
$stmt->execute($params);
$trabalhos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. contagem total p paginação
$sql_total = "SELECT COUNT(*) FROM portfolio p INNER JOIN estilo e ON p.id_estilo = e.id_estilo";
if ($estilo_filtro !== 'todos') {
    $sql_total .= " WHERE e.nome = :estilo";
}
$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute($params);
$total_registros = $stmt_total->fetchColumn();
$total_paginas = ceil($total_registros / $itens_por_pagina);
