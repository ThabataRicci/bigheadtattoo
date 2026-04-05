<?php
// 1. Conexão com o Banco de Dados (Locaweb/Rocky 8)
require_once __DIR__ . '/../includes/conexao.php';

// Verificação de segurança da variável de conexão
if (!isset($pdo)) {
    die("Erro: Falha na conexão com o banco de dados.");
}

// --- LÓGICA DE FILTRO ---
// Pega o estilo da URL (ex: ?estilo=Realismo). Se não existir, assume 'todos'.
$estilo_selecionado = isset($_GET['estilo']) ? $_GET['estilo'] : 'todos';

// --- BUSCA DE ESTILOS PARA O DROPDOWN (A DROPLIST) ---
try {
    $sql_estilos = "SELECT id_estilo, nome FROM estilo ORDER BY nome ASC";
    $lista_estilos = $pdo->query($sql_estilos)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_estilos = [];
}

// --- LÓGICA DE PAGINAÇÃO ---
$itens_por_pagina = 8;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// --- CONSTRUÇÃO DA QUERY COM FILTRO ---
$params = [];
$where = "";

if ($estilo_selecionado !== 'todos') {
    // IMPORTANTE: No Linux (Rocky 8), nomes de colunas e valores são case-sensitive
    $where = " WHERE e.nome = :estilo";
    $params[':estilo'] = $estilo_selecionado;
}

// --- 1. CONTAGEM TOTAL (Para a paginação funcionar com o filtro) ---
try {
    $sql_total = "SELECT COUNT(*) FROM portfolio p 
                  INNER JOIN estilo e ON p.id_estilo = e.id_estilo 
                  $where";
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params);
    $total_registros = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_registros / $itens_por_pagina);
} catch (PDOException $e) {
    $total_paginas = 1;
}

// --- 2. BUSCA DOS TRABALHOS (A Galeria) ---
try {
    $sql_port = "SELECT p.*, e.nome as estilo_nome 
                 FROM portfolio p 
                 INNER JOIN estilo e ON p.id_estilo = e.id_estilo 
                 $where 
                 ORDER BY p.data_publicacao DESC 
                 LIMIT " . (int)$itens_por_pagina . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql_port);
    $stmt->execute($params);
    $trabalhos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $trabalhos = [];
}