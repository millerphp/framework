<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Excalibur\Support\Facades\Facade;
use Excalibur\Router\Router;
use Excalibur\Router\Route;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearFacades();
    }

    protected function clearFacades(): void
    {
        Facade::clearResolvedInstances();
    }

    /**
     * Create a new router instance
     */
    protected function createRouter(): Router
    {
        $router = new Router();
        \Excalibur\Router\Facades\Route::setRouter($router);
        return $router;
    }
} 