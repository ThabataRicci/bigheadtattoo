<?php

date_default_timezone_set('America/Sao_Paulo');

// dados do BD
$host = 'db_studiobig.mysql.dbaas.com.br';
$dbname = 'db_studiobig';
$usuario = 'db_studiobig';
$senha = 'RpsGdPjd6A93g#';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
