<?php
// 1. Caminho absoluto para garantir a conexão
require_once __DIR__ . '/../includes/conexao.php';

// 2. Validação da conexão
if (!isset($pdo)) {
    die("Erro: A variável de conexão \$pdo não foi encontrada. Verifique o arquivo includes/conexao.php");
}

// --- LÓGICA DE PAGINAÇÃO ---
$itens_por_pagina = 8;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// --- FILTRO POR ESTILO ---
$estilo_selecionado = isset($_GET['estilo']) ? $_GET['estilo'] : 'todos';

// --- BUSCA DE ESTILOS PARA O DROPDOWN ---
$query_estilos = $pdo->query("SELECT id_estilo, nome FROM estilo ORDER BY nome ASC");
$lista_estilos = $query_estilos->fetchAll(PDO::FETCH_ASSOC);

// --- 1. QUERY DE CONTAGEM TOTAL (Necessária para a paginação respeitar o filtro) ---
$sql_total = "SELECT COUNT(*) FROM portfolio p INNER JOIN estilo e ON p.id_estilo = e.id_estilo";
$params_filtro = [];

if ($estilo_selecionado !== 'todos') {
    $sql_total .= " WHERE e.nome = :estilo";
    $params_filtro[':estilo'] = $estilo_selecionado;
}

$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute($params_filtro);
$total_registros = $stmt_total->fetchColumn();
$total_paginas = ceil($total_registros / $itens_por_pagina);

// --- 2. QUERY DOS TRABALHOS (Com LIMIT e OFFSET) ---
$sql_port = "SELECT p.*, e.nome as estilo_nome 
             FROM portfolio p 
             INNER JOIN estilo e ON p.id_estilo = e.id_estilo";

if ($estilo_selecionado !== 'todos') {
    $sql_port .= " WHERE e.nome = :estilo";
}

$sql_port .= " ORDER BY p.data_publicacao DESC";
$sql_port .= " LIMIT " . (int)$itens_por_pagina . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql_port);
// Usamos o mesmo array de parâmetros do filtro aqui
$stmt->execute($params_filtro);
$trabalhos = $stmt->fetchAll(PDO::FETCH_ASSOC);