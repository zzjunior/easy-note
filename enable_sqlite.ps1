# Script para habilitar SQLite no PHP
$phpIniPath = "C:\php\php-8.2.27\php.ini"

# Faz backup do php.ini
Copy-Item $phpIniPath "$phpIniPath.backup"

# Lê o conteúdo do arquivo
$content = Get-Content $phpIniPath

# Processa linha por linha
$newContent = @()
foreach ($line in $content) {
    if ($line -match "^;extension=pdo_sqlite") {
        $newContent += "extension=pdo_sqlite"
        Write-Host "Habilitando pdo_sqlite..."
    }
    elseif ($line -match "^;extension=sqlite3") {
        $newContent += "extension=sqlite3"
        Write-Host "Habilitando sqlite3..."
    }
    else {
        $newContent += $line
    }
}

# Salva o arquivo modificado
$newContent | Set-Content $phpIniPath

Write-Host "SQLite habilitado com sucesso!"
Write-Host "Backup salvo em: $phpIniPath.backup"
Write-Host "Reinicie o servidor web para aplicar as mudanças."
