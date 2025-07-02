<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class AutomationController
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // GET /boards/{id}/automations - Lista automações de um board
    public function list(Request $request, Response $response, array $args): Response
    {
        try {
            $boardId = $args['board_id'];
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM automations 
                WHERE board_id = ? 
                ORDER BY name
            ");
            $stmt->execute([$boardId]);
            $automations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Decodifica JSON para facilitar uso no frontend
            foreach ($automations as &$automation) {
                $automation['trigger_config'] = json_decode($automation['trigger_config'], true);
                $automation['action_config'] = json_decode($automation['action_config'], true);
            }

            $response->getBody()->write(json_encode($automations));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // POST /automations - Cria uma automação
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody(), true);
            
            $required = ['board_id', 'name', 'trigger_type', 'action_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode(['error' => "Campo '$field' é obrigatório"]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO automations 
                (board_id, name, description, trigger_type, trigger_config, action_type, action_config, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['board_id'],
                $data['name'],
                $data['description'] ?? '',
                $data['trigger_type'],
                json_encode($data['trigger_config'] ?? []),
                $data['action_type'],
                json_encode($data['action_config'] ?? []),
                $data['is_active'] ?? 1
            ]);

            $automationId = $this->pdo->lastInsertId();
            
            // Retorna a automação criada
            $stmt = $this->pdo->prepare("SELECT * FROM automations WHERE id = ?");
            $stmt->execute([$automationId]);
            $automation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $automation['trigger_config'] = json_decode($automation['trigger_config'], true);
            $automation['action_config'] = json_decode($automation['action_config'], true);

            $response->getBody()->write(json_encode($automation));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // PUT /automations/{id} - Atualiza uma automação
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $automationId = $args['id'];
            $data = json_decode($request->getBody(), true);

            $stmt = $this->pdo->prepare("
                UPDATE automations 
                SET name = ?, description = ?, trigger_type = ?, trigger_config = ?, 
                    action_type = ?, action_config = ?, is_active = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['trigger_type'],
                json_encode($data['trigger_config'] ?? []),
                $data['action_type'],
                json_encode($data['action_config'] ?? []),
                $data['is_active'] ?? 1,
                $automationId
            ]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['error' => 'Automação não encontrada']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Retorna a automação atualizada
            $stmt = $this->pdo->prepare("SELECT * FROM automations WHERE id = ?");
            $stmt->execute([$automationId]);
            $automation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $automation['trigger_config'] = json_decode($automation['trigger_config'], true);
            $automation['action_config'] = json_decode($automation['action_config'], true);

            $response->getBody()->write(json_encode($automation));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // DELETE /automations/{id} - Remove uma automação
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $automationId = $args['id'];

            $stmt = $this->pdo->prepare("DELETE FROM automations WHERE id = ?");
            $stmt->execute([$automationId]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['error' => 'Automação não encontrada']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['message' => 'Automação removida com sucesso']));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // POST /automations/{id}/toggle - Ativa/desativa uma automação
    public function toggle(Request $request, Response $response, array $args): Response
    {
        try {
            $automationId = $args['id'];

            $stmt = $this->pdo->prepare("
                UPDATE automations 
                SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END 
                WHERE id = ?
            ");
            $stmt->execute([$automationId]);

            if ($stmt->rowCount() === 0) {
                $response->getBody()->write(json_encode(['error' => 'Automação não encontrada']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Retorna o novo status
            $stmt = $this->pdo->prepare("SELECT is_active FROM automations WHERE id = ?");
            $stmt->execute([$automationId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'message' => 'Status da automação atualizado',
                'is_active' => (bool)$result['is_active']
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // Executa automações baseadas em eventos
    public function executeAutomations($eventType, $eventData)
    {
        try {
            // Busca automações ativas para o board que correspondem ao evento
            $stmt = $this->pdo->prepare("
                SELECT * FROM automations 
                WHERE board_id = ? AND trigger_type = ? AND is_active = 1
            ");
            $stmt->execute([$eventData['board_id'], $eventType]);
            $automations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($automations as $automation) {
                $triggerConfig = json_decode($automation['trigger_config'], true);
                $actionConfig = json_decode($automation['action_config'], true);

                // Verifica se o trigger se aplica
                if ($this->shouldTrigger($eventType, $triggerConfig, $eventData)) {
                    $this->executeAction($automation['action_type'], $actionConfig, $eventData);
                }
            }
            
        } catch (\Exception $e) {
            error_log("Erro ao executar automações: " . $e->getMessage());
        }
    }

    private function shouldTrigger($eventType, $triggerConfig, $eventData)
    {
        switch ($eventType) {
            case 'card_moved':
                if (isset($triggerConfig['to_list_name'])) {
                    return $eventData['to_list_name'] === $triggerConfig['to_list_name'];
                }
                if (isset($triggerConfig['from_list_name'])) {
                    return $eventData['from_list_name'] === $triggerConfig['from_list_name'];
                }
                return true;
                
            case 'card_created':
                if (isset($triggerConfig['in_list_name'])) {
                    return $eventData['list_name'] === $triggerConfig['in_list_name'];
                }
                return true;
                
            default:
                return true;
        }
    }

    private function executeAction($actionType, $actionConfig, $eventData)
    {
        switch ($actionType) {
            case 'add_tag':
                $this->addTagToCard($actionConfig['tag_name'], $eventData['card_id']);
                break;
                
            case 'move_card':
                $this->moveCard($eventData['card_id'], $actionConfig['to_list_name']);
                break;
                
            // Outras ações podem ser adicionadas aqui
        }
    }

    private function addTagToCard($tagName, $cardId)
    {
        try {
            // Busca a tag pelo nome
            $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $stmt->execute([$tagName]);
            $tag = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tag) {
                // Adiciona a tag ao card
                $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO card_tags (card_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$cardId, $tag['id']]);
            }
        } catch (\Exception $e) {
            error_log("Erro ao adicionar tag: " . $e->getMessage());
        }
    }

    private function moveCard($cardId, $toListName)
    {
        try {
            // Busca a lista pelo nome
            $stmt = $this->pdo->prepare("SELECT id FROM lists WHERE title = ?");
            $stmt->execute([$toListName]);
            $list = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($list) {
                // Move o card
                $stmt = $this->pdo->prepare("UPDATE cards SET list_id = ? WHERE id = ?");
                $stmt->execute([$list['id'], $cardId]);
            }
        } catch (\Exception $e) {
            error_log("Erro ao mover card: " . $e->getMessage());
        }
    }
}
?>
