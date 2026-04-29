<?php

namespace Core;

// On importe ta classe d'attribut et on lui donne un surnom
use Core\Attributes\Route as RouteAttribute;
use ReflectionClass;

class Router
{
    private array $routes = [];

    /**
     * Scanne un dossier pour trouver les contrôleurs et leurs routes
     */
    public function registerControllers(array $controllers): void
    {
        foreach ($controllers as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);

            foreach ($reflection->getMethods() as $method) {
                // On cherche l'attribut #[Route] au-dessus de la méthode
                $attributes = $method->getAttributes(RouteAttribute::class);

                foreach ($attributes as $attribute) {
                    $routeAttr = $attribute->newInstance();

                    // On enregistre la route automatiquement
                    $this->routes[$routeAttr->method][] = new Route(
                        $routeAttr->path,
                        $reflection->getShortName() . '@' . $method->getName(),
                        $routeAttr->namespace,
                    );
                }
            }
        }
    }

    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][] = new Route($path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][] = new Route($path, $handler);
    }

    public function resolve(string $uri, string $method)
    {
        // On cherche dans le tableau correspondant à la méthode (GET ou POST)
        if (!isset($this->routes[$method])) {
            http_response_code(404);
            echo "Méthode non autorisée.";
            return;
        }

        foreach ($this->routes[$method] as $route) {
            if ($route->match($uri)) {
                return $route->execute();
            }
        }

        http_response_code(404);
        echo "404 - Page non trouvée";
    }
}