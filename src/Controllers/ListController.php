<?php
namespace App\Controllers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ListController {
    private $db;
    
    public function __construct(\PDO $db){
        $this->db = $db;
    }

    // Listar todas as listas de um board
    public function listByBoard(Request $req, Response $res, $args){
        $boardId = $args['boardId'];
        $stmt = $this->db->prepare('SELECT * FROM lists WHERE board_id = ? ORDER BY position');
        $stmt->execute([$boardId]);
        $lists = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $res->getBody()->write(json_encode($lists));
        return $res->withHeader('Content-Type','application/json');
    }

    // Criar nova lista
    public function create(Request $req, Response $res, $args){
        $data = json_decode($req->getBody()->getContents(), true);
        
        if (!isset($data['title']) || empty(trim($data['title']))) {
            $error = ['error' => 'Título é obrigatório'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        if (!isset($data['board_id'])) {
            $error = ['error' => 'ID do board é obrigatório'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        // Pega a próxima posição
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(position), 0) + 1 as next_position FROM lists WHERE board_id = ?');
        $stmt->execute([$data['board_id']]);
        $nextPosition = $stmt->fetch(\PDO::FETCH_ASSOC)['next_position'];
        
        $stmt = $this->db->prepare('INSERT INTO lists (board_id, title, position, created_at) VALUES (?, ?, ?, datetime("now"))');
        $stmt->execute([$data['board_id'], trim($data['title']), $nextPosition]);
        $id = $this->db->lastInsertId();
        
        // Busca a lista criada
        $stmt = $this->db->prepare('SELECT * FROM lists WHERE id = ?');
        $stmt->execute([$id]);
        $list = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $res->getBody()->write(json_encode($list));
        return $res->withHeader('Content-Type','application/json')->withStatus(201);
    }

    // Atualizar lista
    public function update(Request $req, Response $res, $args){
        $listId = $args['listId'];
        $data = json_decode($req->getBody()->getContents(), true);
        
        if (!isset($data['title']) || empty(trim($data['title']))) {
            $error = ['error' => 'Título é obrigatório'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        $stmt = $this->db->prepare('UPDATE lists SET title = ? WHERE id = ?');
        $stmt->execute([trim($data['title']), $listId]);
        
        // Busca a lista atualizada
        $stmt = $this->db->prepare('SELECT * FROM lists WHERE id = ?');
        $stmt->execute([$listId]);
        $list = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$list) {
            $error = ['error' => 'Lista não encontrada'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(404);
        }
        
        $res->getBody()->write(json_encode($list));
        return $res->withHeader('Content-Type','application/json');
    }

    // Excluir lista
    public function delete(Request $req, Response $res, $args){
        $listId = $args['listId'];
        
        $stmt = $this->db->prepare('DELETE FROM lists WHERE id = ?');
        $stmt->execute([$listId]);
        
        $res->getBody()->write(json_encode(['message' => 'Lista excluída com sucesso']));
        return $res->withHeader('Content-Type','application/json');
    }
}
?>
