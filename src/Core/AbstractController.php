<?php

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class AbstractController
{
    private $twig;

    public function __construct()
    {
        // 1. Démarrer la session avec une configuration de sécurité explicite
        $this->ensureSessionStarted();

        // Logger::getInstance()->log('Controller instantiated: ' . static::class);

        $requestUri = $_SERVER["REQUEST_URI"] ?? '';
        $requestMethod = $_SERVER["REQUEST_METHOD"] ?? '';

        Logger::getInstance()->info("Request: $requestMethod $requestUri | Controller: " . static::class);



        // 2. Initialiser Twig
        $loader = new FilesystemLoader(__DIR__ . '/../Views');
        $this->twig = new Environment($loader, [
            'cache' => false,
        ]);

        // 3. Variables globales
        $this->twig->addGlobal('user', $_SESSION['user_name'] ?? null);

        // SYSTÈME DE FLASH : On injecte les messages et on les vide de la session
        $this->twig->addGlobal('flashes', $_SESSION['flashes'] ?? []);
        unset($_SESSION['flashes']);

        // VERSION : Lecture du fichier VERSION à la racine
        $versionPath = __DIR__ . '/../../VERSION';
        $version = file_exists($versionPath) ? trim(file_get_contents($versionPath)) : '0.0.0';
        $this->twig->addGlobal('app_version', $version);

        $this->twig->addGlobal('csrf_token', CsrfHelper::getToken());
    }

    /**
     * Ajoute un message flash en session
     * @param string $type (success, error, warning, info)
     */
    protected function addFlash(string $type, string $message): void
    {
        $_SESSION['flashes'][$type][] = $message;
    }

    protected function render(string $view, array $data = [])
    {
        // Twig s'occupe de tout : extract, chargement, affichage
        echo $this->twig->render($view . '.twig', $data);
    }

    /**
     * Démarre la session avec des flags de sécurité explicites.
     */
    protected function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) === '443');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Protège une route : redirige vers login si l'utilisateur n'est pas connecté
     */
    protected function denyAccessUnlessGranted(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            header('Location: /login');
            exit;
        }
    }
}