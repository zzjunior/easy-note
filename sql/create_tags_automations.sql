-- Criação das tabelas para Tags
CREATE TABLE IF NOT EXISTS tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) NOT NULL DEFAULT '#6B7280', -- Cor em hex
    board_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
    UNIQUE(name, board_id) -- Tag única por board
);

-- Tabela de relacionamento Many-to-Many entre cards e tags
CREATE TABLE IF NOT EXISTS card_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    card_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    UNIQUE(card_id, tag_id) -- Evita tags duplicadas no mesmo card
);

-- Tabela para automações
CREATE TABLE IF NOT EXISTS automations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    board_id INTEGER NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    trigger_type VARCHAR(50) NOT NULL, -- 'card_moved', 'card_created', 'tag_added', etc
    trigger_config JSON, -- Configurações específicas do trigger
    action_type VARCHAR(50) NOT NULL, -- 'add_tag', 'move_card', 'notify', etc
    action_config JSON, -- Configurações específicas da action
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE
);

-- Índices para performance
CREATE INDEX IF NOT EXISTS idx_tags_board ON tags(board_id);
CREATE INDEX IF NOT EXISTS idx_card_tags_card ON card_tags(card_id);
CREATE INDEX IF NOT EXISTS idx_card_tags_tag ON card_tags(tag_id);
CREATE INDEX IF NOT EXISTS idx_automations_board ON automations(board_id);
CREATE INDEX IF NOT EXISTS idx_automations_active ON automations(is_active);
