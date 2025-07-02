<?php
namespace App\Controllers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class CardController {
    private $db;
    
    public function __construct(\PDO $db){
        $this->db = $db;
    }

    // Listar todos os cards de uma lista
    public function listByList(Request $req, Response $res, $args){
        $listId = $args['listId'];
        $stmt = $this->db->prepare('SELECT * FROM cards WHERE list_id = ? ORDER BY position');
        $stmt->execute([$listId]);
        $cards = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Para cada card, busca suas tags
        foreach ($cards as &$card) {
            $stmt = $this->db->prepare('
                SELECT t.* FROM tags t 
                INNER JOIN card_tags ct ON t.id = ct.tag_id 
                WHERE ct.card_id = ? 
                ORDER BY t.name
            ');
            $stmt->execute([$card['id']]);
            $card['tags'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        $res->getBody()->write(json_encode($cards));
        return $res->withHeader('Content-Type','application/json');
    }

    // Criar novo card
    public function create(Request $req, Response $res, $args){
        $data = json_decode($req->getBody()->getContents(), true);
        
        if (!isset($data['title']) || empty(trim($data['title']))) {
            $error = ['error' => 'Título é obrigatório'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        if (!isset($data['list_id'])) {
            $error = ['error' => 'ID da lista é obrigatório'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        // Pega a próxima posição
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(position), 0) + 1 as next_position FROM cards WHERE list_id = ?');
        $stmt->execute([$data['list_id']]);
        $nextPosition = $stmt->fetch(\PDO::FETCH_ASSOC)['next_position'];
        
        $description = isset($data['description']) ? trim($data['description']) : '';
        
        $stmt = $this->db->prepare('INSERT INTO cards (list_id, title, description, position, created_at) VALUES (?, ?, ?, ?, datetime("now"))');
        $stmt->execute([$data['list_id'], trim($data['title']), $description, $nextPosition]);
        $id = $this->db->lastInsertId();
        
        // Busca o card criado
        $stmt = $this->db->prepare('SELECT * FROM cards WHERE id = ?');
        $stmt->execute([$id]);
        $card = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $res->getBody()->write(json_encode($card));
        return $res->withHeader('Content-Type','application/json')->withStatus(201);
    }

    // Atualizar card
    public function update(Request $req, Response $res, $args){
        $cardId = $args['cardId'];
        $body = $req->getBody()->getContents();
        $data = json_decode($body, true);
        
        // Log para debug
        error_log("CardController::update - CardID: $cardId");
        error_log("CardController::update - Body: " . $body);
        error_log("CardController::update - Data: " . json_encode($data));
        
        // Se está movendo apenas a lista (drag & drop)
        if (isset($data['list_id']) && count($data) === 1) {
            error_log("CardController::update - Executando movimentação inline");
            
            // Pega a próxima posição na lista destino
            $stmt = $this->db->prepare('SELECT COALESCE(MAX(position), 0) + 1 as next_position FROM cards WHERE list_id = ?');
            $stmt->execute([$data['list_id']]);
            $nextPosition = $stmt->fetch(\PDO::FETCH_ASSOC)['next_position'];
            
            $stmt = $this->db->prepare('UPDATE cards SET list_id = ?, position = ? WHERE id = ?');
            $stmt->execute([$data['list_id'], $nextPosition, $cardId]);
            
            // Busca o card atualizado com tags
            $stmt = $this->db->prepare('SELECT * FROM cards WHERE id = ?');
            $stmt->execute([$cardId]);
            $card = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$card) {
                $error = ['error' => 'Card não encontrado'];
                $res->getBody()->write(json_encode($error));
                return $res->withHeader('Content-Type','application/json')->withStatus(404);
            }
            
            // Busca as tags do card
            $stmt = $this->db->prepare('
                SELECT t.* FROM tags t 
                INNER JOIN card_tags ct ON t.id = ct.tag_id 
                WHERE ct.card_id = ? 
                ORDER BY t.name
            ');
            $stmt->execute([$cardId]);
            $card['tags'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $res->getBody()->write(json_encode($card));
            return $res->withHeader('Content-Type','application/json');
        }
        
        if (!isset($data['title']) || empty(trim($data['title']))) {
            $error = ['error' => 'Título é obrigatório'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        $description = isset($data['description']) ? trim($data['description']) : '';
        $listId = isset($data['list_id']) ? $data['list_id'] : null;
        
        if ($listId) {
            // Atualiza card com nova lista
            $stmt = $this->db->prepare('UPDATE cards SET title = ?, description = ?, list_id = ? WHERE id = ?');
            $stmt->execute([trim($data['title']), $description, $listId, $cardId]);
        } else {
            // Atualiza apenas título e descrição
            $stmt = $this->db->prepare('UPDATE cards SET title = ?, description = ? WHERE id = ?');
            $stmt->execute([trim($data['title']), $description, $cardId]);
        }
        
        // Busca o card atualizado com tags
        $stmt = $this->db->prepare('SELECT * FROM cards WHERE id = ?');
        $stmt->execute([$cardId]);
        $card = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$card) {
            $error = ['error' => 'Card não encontrado'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(404);
        }
        
        // Busca as tags do card
        $stmt = $this->db->prepare('
            SELECT t.* FROM tags t 
            INNER JOIN card_tags ct ON t.id = ct.tag_id 
            WHERE ct.card_id = ? 
            ORDER BY t.name
        ');
        $stmt->execute([$cardId]);
        $card['tags'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $res->getBody()->write(json_encode($card));
        return $res->withHeader('Content-Type','application/json');
    }

    // Mover card para outra lista
    public function move(Request $req, Response $res, $args){
        $cardId = $args['cardId'];
        $data = json_decode($req->getBody()->getContents(), true);
        
        if (!isset($data['list_id'])) {
            $error = ['error' => 'ID da lista destino é obrigatório'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        // Pega a próxima posição na lista destino
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(position), 0) + 1 as next_position FROM cards WHERE list_id = ?');
        $stmt->execute([$data['list_id']]);
        $nextPosition = $stmt->fetch(\PDO::FETCH_ASSOC)['next_position'];
        
        $stmt = $this->db->prepare('UPDATE cards SET list_id = ?, position = ? WHERE id = ?');
        $stmt->execute([$data['list_id'], $nextPosition, $cardId]);
        
        // Busca o card atualizado
        $stmt = $this->db->prepare('SELECT * FROM cards WHERE id = ?');
        $stmt->execute([$cardId]);
        $card = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $res->getBody()->write(json_encode($card));
        return $res->withHeader('Content-Type','application/json');
    }

    // Excluir card
    public function delete(Request $req, Response $res, $args){
        $cardId = $args['cardId'];
        
        $stmt = $this->db->prepare('DELETE FROM cards WHERE id = ?');
        $stmt->execute([$cardId]);
        
        $res->getBody()->write(json_encode(['message' => 'Card excluído com sucesso']));
        return $res->withHeader('Content-Type','application/json');
    }

    // Buscar um card específico por ID
    public function get(Request $req, Response $res, $args){
        $cardId = $args['cardId'];
        
        // Busca o card
        $stmt = $this->db->prepare('SELECT * FROM cards WHERE id = ?');
        $stmt->execute([$cardId]);
        $card = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$card) {
            $error = ['error' => 'Card não encontrado'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(404);
        }
        
        // Busca as tags do card
        $stmt = $this->db->prepare('
            SELECT t.* FROM tags t 
            INNER JOIN card_tags ct ON t.id = ct.tag_id 
            WHERE ct.card_id = ? 
            ORDER BY t.name
        ');
        $stmt->execute([$cardId]);
        $card['tags'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $res->getBody()->write(json_encode($card));
        return $res->withHeader('Content-Type','application/json');
    }
}
?>
