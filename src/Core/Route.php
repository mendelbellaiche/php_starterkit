<?php

namespace StarterKit\Core;

class Route
{
    private string $path;
    private string $handler;
    private array $matches = [];
    private string $namespace;

    public function __construct(string $path, string $handler, string $namespace = CONTROLLER_NAMESPACE)
    {
        // On nettoie le chemin pour éviter les doubles slashs
        $this->path = trim($path, '/');
        $this->handler = $handler;
        $this->namespace = $namespace;
    }

    /**
     * Vérifie si l'URL correspond à la route
     */
    public function match(string $url): bool
    {
        $url = trim($url, '/');

        // On transforme la route en Expression Régulière pour gérer les paramètres {id}
        // Exemple : /cards/{id} devient ^cards/([^/]+)$
        $pathRegex = preg_replace('#\{([\w]+)\}#', '([^/]+)', $this->path);
        $regex = "#^$pathRegex$#i";

        if (!preg_match($regex, $url, $matches)) {
            return false;
        }

        // On stocke les captures (ex: l'ID) pour les passer au contrôleur
        array_shift($matches);
        $this->matches = $matches;

        return true;
    }

    /**
     * Exécute le contrôleur associé à la route
     */
    public function execute()
    {
        [$controllerName, $methodName] = explode('@', $this->handler);

        $controllerPath = $this->namespace . $controllerName;

        if (class_exists($controllerPath)) {
            $controller = new $controllerPath();
            // On appelle la méthode en lui passant les paramètres capturés (ex: $id)
            return call_user_func_array([$controller, $methodName], $this->matches);
        }

        throw new \Exception("Le contrôleur $controllerPath est introuvable.");
    }

}