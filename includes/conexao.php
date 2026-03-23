<?php

// 1. AJUSTE DO PHP: Define o fuso horário para funções do próprio PHP (ex: date())
date_default_timezone_set('America/Sao_Paulo');

// dados do Clever Cloud (banco de dados)
$host = 'b8tb9bi6dvcmeqmxoqaa-mysql.services.clever-cloud.com';
$dbname = 'b8tb9bi6dvcmeqmxoqaa';
$usuario = 'uixv1emjcpbavzst';
$senha = 'CbZpO3OQY9OIEgNFuxYW';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. AJUSTE DO BANCO: Define o fuso horário para a conexão atual
    // Isso faz com que funções SQL como NOW() usem o horário de Brasília
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
