<?php

declare(strict_types=1);

namespace Excalibur\Foundation;

use Excalibur\App;
use Excalibur\HTTP\Request;
use Excalibur\Router\Facades\Route;
use Excalibur\HTTP\Facades\Request as RequestFacade;

class Application
{
    private static ?self $instance = null;
    protected App $app;
    protected string $basePath;
    protected string $routesPath;
    protected string $bootstrapPath;

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function __construct(string $basePath)
    {
        self::$instance = $this;
        $this->app = App::create();
        
        // Normalize the base path first
        $normalizedBasePath = rtrim($basePath, '\/');
        
        // Set base path after normalization
        $this->basePath = $normalizedBasePath;
        
        // Set derived paths
        $this->routesPath = $this->normalizePath($normalizedBasePath . '/routes');
        $this->bootstrapPath = $this->normalizePath($normalizedBasePath . '/bootstrap');
        
        $this->bootstrap();
    }

    protected function bootstrap(): void
    {
        // Set up router
        Route::setRouter($this->app->getRouter());
        
        // Load routes
        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        $routesPath = $this->basePath . '/routes/web.php';
        
        if (file_exists($routesPath)) {
            require_once $routesPath;
        }
    }

    protected function normalizePath(string $path): string
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function run(): void
    {
        try {
            $response = $this->app->getRouter()->dispatch(
                RequestFacade::uri(),
                $this->app->getRequest()
            );
            
            $this->sendResponse($response);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    protected function sendResponse(mixed $response): void
    {
        if (is_string($response) || is_numeric($response)) {
            echo $response;
        } elseif (is_array($response)) {
            header('Content-Type: application/json');
            echo json_encode($response);
        }
        // Add more response type handling as needed
    }

    protected function handleException(\Exception $e): void
    {
        http_response_code($e->getCode() ?: 500);
        echo $e->getMessage();
    }
} 