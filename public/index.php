<?php

// ─── Headers de sécurité ──────────────────────────────────────────────────────

// Empêche le clickjacking (iframes)
header('X-Frame-Options: DENY');

// Empêche le sniffing de Content-Type par le navigateur
header('X-Content-Type-Options: nosniff');

// Contrôle les infos envoyées dans le Referer
header('Referrer-Policy: strict-origin-when-cross-origin');

// Force HTTPS (activer uniquement si ton site est en HTTPS)
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Content Security Policy — adapte les sources selon tes besoins
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com; object-src 'none'; frame-ancestors 'none';");

// avant tout accès à $_SESSION
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',       // laisse vide si tu n'as pas besoin d'un domaine spécifique
    'secure' => $secure,  // true uniquement en HTTPS
    'httponly' => true,   // empêche JS de lire le cookie
    'samesite' => 'Lax',  // ou 'Strict' selon UX
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Core\Router;

// Charger la config
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$router = new Core\Router();

// On enregistre les contrôleurs (on pourrait même automatiser le scan du dossier)
$router->registerControllers([
    Controllers\AuthController::class,
    Controllers\HomeController::class,
]);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$router->resolve($uri, $method);