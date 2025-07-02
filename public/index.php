<?php
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

// Cria o container manualmente
$container = new Container();
AppFactory::setContainer($container);

// Cria o app
$app = AppFactory::create();

// Injeta o container no app
(require __DIR__ . '/../src/dependencies.php')($container);
(require __DIR__ . '/../src/routes.php')($app);

// Rota para servir a página principal
$app->get('/', function($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/index.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// Rota para servir a página do board
$app->get('/board.html', function($request, $response, $args) {
    $html = file_get_contents(__DIR__ . '/board.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->run();