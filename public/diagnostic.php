<?php
// Teste de diagnóstico do SQLite
echo "<h1>Diagnóstico SQLite</h1>";

// Verifica extensões carregadas
echo "<h2>Extensões PDO carregadas:</h2>";
$extensions = get_loaded_extensions();
$pdoExtensions = array_filter($extensions, function($ext) {
    return strpos($ext, 'pdo') !== false || strpos($ext, 'sqlite') !== false;
});

foreach ($pdoExtensions as $ext) {
    echo "✅ $ext<br>";
}

// Verifica drivers PDO disponíveis
echo "<h2>Drivers PDO disponíveis:</h2>";
$drivers = PDO::getAvailableDrivers();
foreach ($drivers as $driver) {
    echo "✅ $driver<br>";
}

// Testa conexão SQLite
echo "<h2>Teste de conexão SQLite:</h2>";
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/../db/notas.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexão SQLite: OK<br>";
    
    // Testa consulta
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM boards');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Query funcionando: {$result['total']} boards encontrados<br>";
    
    // Mostra estrutura da tabela
    echo "<h3>Estrutura da tabela boards:</h3>";
    $stmt = $pdo->query("PRAGMA table_info(boards)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['name']} ({$column['type']})<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
}

echo "<h2>Informações PHP:</h2>";
echo "Versão PHP: " . phpversion() . "<br>";
echo "php.ini: " . php_ini_loaded_file() . "<br>";
?>
