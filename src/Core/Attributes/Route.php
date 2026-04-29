<?php

namespace Core\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)] // On indique que cet attribut ne va que sur des fonctions
class Route
{
    public function __construct(
        public string $path,
        public string $method = 'GET',
        public string $namespace = CONTROLLER_NAMESPACE,
    ) {}
}