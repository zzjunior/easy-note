<?php
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Controllers\BoardController;
use App\Controllers\ListController;
use App\Controllers\CardController;
use App\Controllers\TagController;
use App\Controllers\AutomationController;

return function(App $app){
    // Rotas de Boards
    $app->get('/boards', BoardController::class . ':list');
    $app->post('/boards', BoardController::class . ':create');
    
    // Rotas de Listas
    $app->get('/boards/{boardId}/lists', ListController::class . ':listByBoard');
    $app->post('/lists', ListController::class . ':create');
    $app->put('/lists/{listId}', ListController::class . ':update');
    $app->delete('/lists/{listId}', ListController::class . ':delete');
    
    // Rotas de Cards
    $app->get('/lists/{listId}/cards', CardController::class . ':listByList');
    $app->get('/cards/{cardId}', CardController::class . ':get');
    $app->post('/cards', CardController::class . ':create');
    $app->put('/cards/{cardId}', CardController::class . ':update');
    $app->put('/cards/{cardId}/move', CardController::class . ':move');
    $app->delete('/cards/{cardId}', CardController::class . ':delete');
    
    // Rotas para Tags
    $app->get('/tags', TagController::class . ':listAll');
    $app->get('/boards/{board_id}/tags', TagController::class . ':list');
    $app->post('/tags', TagController::class . ':create');
    $app->put('/tags/{id}', TagController::class . ':update');
    $app->delete('/tags/{id}', TagController::class . ':delete');
    
    // Rotas para associar tags com cards
    $app->post('/cards/{card_id}/tags/{tag_id}', TagController::class . ':addToCard');
    $app->delete('/cards/{card_id}/tags/{tag_id}', TagController::class . ':removeFromCard');
    $app->get('/cards/{card_id}/tags', TagController::class . ':getCardTags');
    $app->put('/cards/{card_id}/tags', TagController::class . ':updateCardTags');

    // Rotas para Automações
    $app->get('/boards/{board_id}/automations', AutomationController::class . ':list');
    $app->post('/automations', AutomationController::class . ':create');
    $app->put('/automations/{id}', AutomationController::class . ':update');
    $app->delete('/automations/{id}', AutomationController::class . ':delete');
    $app->post('/automations/{id}/toggle', AutomationController::class . ':toggle');
};