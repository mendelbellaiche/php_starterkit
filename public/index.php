<?php

use StarterKit\Core\Router;

// Charger la config
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$router = new StarterKit\Core\Router();

// On enregistre les contrôleurs (on pourrait même automatiser le scan du dossier)
$router->registerControllers([
    StarterKit\Controllers\AuthController::class,
    StarterKit\Controllers\HomeController::class,
]);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$router->resolve($uri, $method);