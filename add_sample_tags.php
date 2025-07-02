<?php
// Script para testar as tags no frontend
// Adiciona tags de exemplo a alguns cards

try {
    $pdo = new PDO('sqlite:db/notas.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🏷️ Adicionando tags de exemplo aos cards...\n\n";
    
    // Busca alguns cards existentes
    $stmt = $pdo->query("SELECT id, title FROM cards LIMIT 3");
    $cards = $stmt->fetchAll();
    
    if (empty($cards)) {
        echo "❌ Nenhum card encontrado. Crie alguns cards primeiro.\n";
        exit;
    }
    
    // Busca algumas tags
    $stmt = $pdo->query("SELECT id, name FROM tags LIMIT 5");
    $tags = $stmt->fetchAll();
    
    if (empty($tags)) {
        echo "❌ Nenhuma tag encontrada. Execute install_tags_automations.php primeiro.\n";
        exit;
    }
    
    // Adiciona tags aos cards
    foreach ($cards as $index => $card) {
        // Adiciona 1-3 tags aleatórias para cada card
        $numTags = rand(1, min(3, count($tags)));
        $selectedTags = array_slice($tags, 0, $numTags);
        
        foreach ($selectedTags as $tag) {
            try {
                $stmt = $pdo->prepare("INSERT OR IGNORE INTO card_tags (card_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$card['id'], $tag['id']]);
                echo "✅ Tag '{$tag['name']}' adicionada ao card '{$card['title']}'\n";
            } catch (Exception $e) {
                // Ignora se já existe
            }
        }
        
        // Rotaciona tags para próximo card
        $tags = array_merge(array_slice($tags, $numTags), array_slice($tags, 0, $numTags));
    }
    
    echo "\n🎉 Tags de exemplo adicionadas com sucesso!\n";
    echo "Agora você pode ver as tags nos cards no frontend.\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
