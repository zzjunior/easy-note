<?php
// Script para inicializar o banco de dados
$pdo = new PDO('sqlite:' . __DIR__ . '/db/notas.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verifica se a tabela exists
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table';")->fetchAll(PDO::FETCH_COLUMN);

if (in_array('boards', $tables)) {
    echo "Tabela 'boards' já existe.\n";
} else {
    echo "Criando tabela 'boards'...\n";
    $sql = file_get_contents(__DIR__ . '/sql/init.sql');
    $pdo->exec($sql);
    echo "Tabela 'boards' criada com sucesso!\n";
}

echo "Banco de dados inicializado!\n";
?>
