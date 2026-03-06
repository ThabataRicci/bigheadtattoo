<?php
// dados do Clever Cloud (banco de dados)
$host = 'b8tb9bi6dvcmeqmxoqaa-mysql.services.clever-cloud.com';
$dbname = 'b8tb9bi6dvcmeqmxoqaa';
$usuario = 'uixv1emjcpbavzst';
$senha = 'CbZpO3OQY9OIEgNFuxYW';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
