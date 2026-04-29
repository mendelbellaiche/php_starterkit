<?php

namespace StarterKit\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class AbstractController
{
    private $twig;

    public function __construct()
    {
        // 1. Démarrer la session si elle n'existe pas
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

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