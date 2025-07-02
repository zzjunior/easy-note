<?php
// Script para atualizar o board.js com as novas funcionalidades

$filePath = 'public/assets/js/board.js';
$content = file_get_contents($filePath);

// Substituir as linhas do onclick viewCard para handleCardClick
$content = str_replace(
    'onclick="boardManager.viewCard(${card.id}, event)"',
    'onclick="boardManager.handleCardClick(${card.id}, event)"',
    $content
);

// Salvar o arquivo
file_put_contents($filePath, $content);

echo "Arquivo board.js atualizado com handleCardClick!\n";
?>
