CREATE TABLE IF NOT EXISTS boards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Inserir alguns dados de exemplo
INSERT OR IGNORE INTO boards (id, title) VALUES 
(1, 'To Do'),
(2, 'In Progress'), 
(3, 'Done');