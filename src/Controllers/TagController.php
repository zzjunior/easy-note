<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class TagController
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // GET /boards/{id}/tags - Lista todas as tags de um board
    public function list(Request $request, Response $response, array $args): Response
    {
        try {
            $boardId = $args['board_id'];
            
            $stmt = $this->pdo->prepare("
                SELECT t.*, 
                       COUNT(ct.card_id) as cards_count
                FROM tags t 
                LEFT JOIN card_tags ct ON t.id = ct.tag_id 
                WHERE t.board_id = ? 
                GROUP BY t.id 
                ORDER BY t.name
            ");
            $stmt->execute([$boardId]);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($tags));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // POST /tags - Cria uma nova tag
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody(), true);
            
            if (empty($data['name']) || empty($data['board_id'])) {
                $response->getBody()->write(json_encode(['error' => 'Nome e board_id são obrigatórios']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $name = trim($data['name']);
            $color = $data['color'] ?? '#6B7280';
            $boardId = $data['board_id'];

            $stmt = $this->pdo->prepare("
                INSERT INTO tags (name, color, board_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$name, $color, $boardId]);

            $tagId = $this->pdo->lastInsertId();
            
            // Retorna a tag criada
            $stmt = $this->pdo->prepare("SELECT * FROM tags WHERE id = ?");
            $stmt->execute([$tagId]);
            $tag = $stmt->fetch(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($tag));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // PUT /tags/{id} - Atualiza uma tag
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $tagId = $args['id'];
            $data = json_decode($request->getBody(), true);

            $name = trim($data['name'] ?? '');
            $color = $data['color'] ?? '';

            if (empty($name)) {
                $response->getBody()->write(json_encode(['error' => 'Nome é obrigatório']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $stmt = $this->pdo->prepare("
                UPDATE tags 
                SET name = ?, color = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $color, $tagId]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['error' => 'Tag não encontrada']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Retorna a tag atualizada
            $stmt = $this->pdo->prepare("SELECT * FROM tags WHERE id = ?");
            $stmt->execute([$tagId]);
            $tag = $stmt->fetch(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($tag));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // DELETE /tags/{id} - Remove uma tag
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $tagId = $args['id'];

            // Remove associações com cards primeiro
            $stmt = $this->pdo->prepare("DELETE FROM card_tags WHERE tag_id = ?");
            $stmt->execute([$tagId]);

            // Remove a tag
            $stmt = $this->pdo->prepare("DELETE FROM tags WHERE id = ?");
            $stmt->execute([$tagId]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['error' => 'Tag não encontrada']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['message' => 'Tag removida com sucesso']));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // POST /cards/{id}/tags/{tag_id} - Adiciona tag a um card
    public function addToCard(Request $request, Response $response, array $args): Response
    {
        try {
            $cardId = $args['card_id'];
            $tagId = $args['tag_id'];

            $stmt = $this->pdo->prepare("
                INSERT OR IGNORE INTO card_tags (card_id, tag_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$cardId, $tagId]);

            $response->getBody()->write(json_encode(['message' => 'Tag adicionada ao card']));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // DELETE /cards/{id}/tags/{tag_id} - Remove tag de um card
    public function removeFromCard(Request $request, Response $response, array $args): Response
    {
        try {
            $cardId = $args['card_id'];
            $tagId = $args['tag_id'];

            $stmt = $this->pdo->prepare("
                DELETE FROM card_tags 
                WHERE card_id = ? AND tag_id = ?
            ");
            $stmt->execute([$cardId, $tagId]);

            $response->getBody()->write(json_encode(['message' => 'Tag removida do card']));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /cards/{id}/tags - Lista tags de um card
    public function getCardTags(Request $request, Response $response, array $args): Response
    {
        try {
            $cardId = $args['card_id'];
            
            $stmt = $this->pdo->prepare("
                SELECT t.* 
                FROM tags t 
                INNER JOIN card_tags ct ON t.id = ct.tag_id 
                WHERE ct.card_id = ? 
                ORDER BY t.name
            ");
            $stmt->execute([$cardId]);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($tags));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // GET /tags - Lista todas as tags (global)
    public function listAll(Request $request, Response $response): Response
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, 
                       COUNT(ct.card_id) as cards_count
                FROM tags t 
                LEFT JOIN card_tags ct ON t.id = ct.tag_id 
                GROUP BY t.id 
                ORDER BY t.name
            ");
            $stmt->execute();
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($tags));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // PUT /cards/{card_id}/tags - Atualiza as tags de um card
    public function updateCardTags(Request $request, Response $response, array $args): Response
    {
        try {
            $cardId = $args['card_id'];
            $data = json_decode($request->getBody(), true);
            
            if (!isset($data['tags']) || !is_array($data['tags'])) {
                $response->getBody()->write(json_encode(['error' => 'Array de tags é obrigatório']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            // Remove todas as tags atuais do card
            $stmt = $this->pdo->prepare("DELETE FROM card_tags WHERE card_id = ?");
            $stmt->execute([$cardId]);

            // Adiciona as novas tags
            $stmt = $this->pdo->prepare("INSERT INTO card_tags (card_id, tag_id) VALUES (?, ?)");
            foreach ($data['tags'] as $tagId) {
                $stmt->execute([$cardId, $tagId]);
            }

            // Retorna as tags atualizadas do card
            $stmt = $this->pdo->prepare("
                SELECT t.* FROM tags t 
                INNER JOIN card_tags ct ON t.id = ct.tag_id 
                WHERE ct.card_id = ? 
                ORDER BY t.name
            ");
            $stmt->execute([$cardId]);
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode(['success' => true, 'tags' => $tags]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
?>
