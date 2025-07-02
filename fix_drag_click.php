<?php
// Script para adicionar cancelCardClick no handleDragStart

$filePath = 'public/assets/js/board.js';
$content = file_get_contents($filePath);

// Substituir a primeira linha do handleDragStart
$content = str_replace(
    'handleDragStart(e) {
        this.draggedCard = e.target;',
    'handleDragStart(e) {
        // Cancela qualquer clique pendente
        this.cancelCardClick();
        
        this.draggedCard = e.target;',
    $content
);

// Salvar o arquivo
file_put_contents($filePath, $content);

echo "Adicionado cancelCardClick ao handleDragStart!\n";
?>
