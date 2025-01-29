<?php

declare(strict_types=1);

namespace Excalibur\HTTP;

class Request
{
    /**
     * The request data.
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * @var array<string, mixed>
     */
    protected array $query;

    /**
     * @var array<string, mixed>
     */
    protected array $server;

    /**
     * @var array<string, mixed>
     */
    protected array $cookies;

    /**
     * @var array<string, mixed>
     */
    protected array $files;

    public function __construct(
        array $query = [],
        array $request = [],
        array $server = [],
        array $cookies = [],
        array $files = []
    ) {
        $this->query = $query;
        $this->data = array_merge($query, $request);
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
    }

    /**
     * Get a specific input value from the request.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get all input values from the request.
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Check if a specific input value exists in the request.
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get the request method (GET, POST, etc.).
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get the request URI.
     */
    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '';
    }

    /**
     * Get a specific header from the request.
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->server['HTTP_' . $key] ?? $default;
    }

    public static function capture(): self
    {
        return new self(
            $_GET,
            $_POST,
            $_SERVER,
            $_COOKIE,
            $_FILES
        );
    }
} 