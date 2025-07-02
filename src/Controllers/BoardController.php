<?php
namespace App\Controllers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class BoardController {
    private $db;
    public function __construct(\PDO $db){
        $this->db = $db;
    }

    public function list(Request $req, Response $res, $args){
        $stmt = $this->db->query('SELECT * FROM boards');
        $boards = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $res->getBody()->write(json_encode($boards));
        return $res->withHeader('Content-Type','application/json');
    }

    public function create(Request $req, Response $res, $args){
        $data = json_decode($req->getBody()->getContents(), true);
        
        if (!isset($data['title']) || empty(trim($data['title']))) {
            $error = ['error' => 'Título é obrigatório'];
            $res->getBody()->write(json_encode($error));
            return $res->withHeader('Content-Type','application/json')->withStatus(400);
        }
        
        $stmt = $this->db->prepare('INSERT INTO boards (title, created_at) VALUES (:title, datetime("now"))');
        $stmt->execute([':title' => trim($data['title'])]);
        $id = $this->db->lastInsertId();
        
        // Busca o board criado para retornar com timestamp
        $stmt = $this->db->prepare('SELECT * FROM boards WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $board = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $res->getBody()->write(json_encode($board));
        return $res->withHeader('Content-Type','application/json')->withStatus(201);
    }
}