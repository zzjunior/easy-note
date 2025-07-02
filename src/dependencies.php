<?php
use DI\Container;
use App\Controllers\BoardController;
use App\Controllers\ListController;
use App\Controllers\CardController;
use App\Controllers\TagController;
use App\Controllers\AutomationController;

return function(Container $container){
    $container->set('db', function(){
        $pdo = new \PDO('sqlite:' . __DIR__ . '/../db/notas.db');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        return $pdo;
    });
    
    // Mapeia PDO para a instância do banco
    $container->set(\PDO::class, function() use ($container) {
        return $container->get('db');
    });
    
    // Registra os controllers
    $container->set('BoardController', function() use ($container) {
        return new BoardController($container->get(PDO::class));
    });

    $container->set('ListController', function() use ($container) {
        return new ListController($container->get(PDO::class));
    });

    $container->set('CardController', function() use ($container) {
        return new CardController($container->get(PDO::class));
    });

    // Novos controllers para tags e automações
    $container->set('TagController', function() use ($container) {
        return new TagController($container->get(PDO::class));
    });

    $container->set('AutomationController', function() use ($container) {
        return new AutomationController($container->get(PDO::class));
    });

    return $container;
};