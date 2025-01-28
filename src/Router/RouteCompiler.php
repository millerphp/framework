<?php

declare(strict_types=1);

namespace Excalibur\Router;

class RouteCompiler
{
    /**
     * @var array<string, string>
     */
    private array $patterns = [
        'id' => '[0-9]+',
        'slug' => '[a-z0-9-]+',
        'any' => '[^/]+',
        'num' => '[0-9]+',
        'alpha' => '[a-zA-Z]+',
        'alphanum' => '[a-zA-Z0-9]+',
    ];

    /**
     * Add a custom pattern
     */
    public function addPattern(string $name, string $pattern): void
    {
        $this->patterns[$name] = $pattern;
    }

    /**
     * Compile the route pattern to regex
     * 
     * @param array<string, string> $constraints
     */
    public function compile(string $uri, array $constraints = []): string
    {
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_-]*):?([^}]*?)(\?)?}/',
            function($matches) use ($constraints) {
                $name = $matches[1];
                $pattern = !empty($matches[2]) ? $matches[2] : '[^/]+';
                $optional = isset($matches[3]);
                
                // Check for constraint first, then pattern map
                if (isset($constraints[$name])) {
                    $pattern = $constraints[$name];
                } elseif (isset($this->patterns[$pattern])) {
                    $pattern = $this->patterns[$pattern];
                }
                
                return sprintf(
                    '(?P<%s>%s)%s',
                    $name,
                    $pattern,
                    $optional ? '?' : ''
                );
            },
            $uri
        );

        return '#^' . $pattern . '$#';
    }

    /**
     * Extract parameters from URI based on pattern
     * @return array<string, string>
     */
    public function extractParameters(string $uri, array $matches): array
    {
        return array_filter(
            $matches,
            fn($key) => is_string($key),
            ARRAY_FILTER_USE_KEY
        );
    }
} 