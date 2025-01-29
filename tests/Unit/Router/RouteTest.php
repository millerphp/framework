<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Tests\Fixtures\Controllers\TestController;
use Excalibur\Router\Route;
use Excalibur\Router\Exception\RouterException;
use Excalibur\HTTP\Request;

describe('Route', function () {
    beforeEach(function () {
        $this->createRouter();
    });

    it('creates GET routes', function () {
        $route = Route::get('/users', fn () => 'users');

        expect($route)
            ->toBeInstanceOf(Route::class)
            ->getMethods()->toBe(['GET']);
    });

    it('creates POST routes', function () {
        $route = Route::post('/users', fn () => 'create user');

        expect($route)
            ->toBeInstanceOf(Route::class)
            ->getMethods()->toBe(['POST']);
    });

    it('creates routes with multiple methods', function () {
        $route = new Route(['GET', 'POST'], '/users', fn () => 'handler');

        expect($route->getMethods())->toBe(['GET', 'POST']);
    });

    it('handles named routes', function () {
        $route = Route::get('/users', fn () => 'users')
            ->name('users.index');

        expect($route->getName())->toBe('users.index');
    });

    it('handles middleware', function () {
        $route = Route::get('/admin', fn () => 'admin')
            ->middleware(['auth', 'admin']);

        expect($route->getMiddleware())->toBe(['auth', 'admin']);
    });

    it('matches URIs with parameters', function () {
        $route = Route::get('/users/{id}', fn ($id) => "User $id");

        expect($route->matches('/users/123', 'GET'))->toBeTrue()
            ->and($route->getParameters())->toBe(['id' => '123']);
    });

    it('handles parameter constraints', function () {
        $route = Route::get('/users/{id}', fn ($id) => "User $id")
            ->where('id', '[0-9]+');

        // Test valid numeric ID
        expect($route->matches('/users/123', 'GET'))->toBeTrue()
            // Test invalid non-numeric ID
            ->and($route->matches('/users/abc', 'GET'))->toBeFalse();
    });

    it('executes callable handlers', function () {
        $route = Route::get('/test', fn () => 'result');

        $request = new Request();
        expect($route->execute($request))->toBe('result');
    });

    it('executes controller method handlers', function () {
        $route = Route::get('/test', TestController::class . '@index');

        $request = new Request();
        expect($route->execute($request))->toBe('controller result');
    });

    it('handles array controller handlers', function () {
        $route = Route::get('/test', [TestController::class, 'index']);
        $route->matches('/test', 'GET');

        $request = new Request();
        expect($route->execute($request))->toBe('controller result');
    });

    it('throws exception for invalid array handler format', function () {
        $route = Route::get('/test', ['invalid']);
        $route->matches('/test', 'GET');

        $request = new Request();
        expect(fn () => $route->execute($request))
            ->toThrow(RouterException::class, 'Invalid controller array format. Expected [Controller::class, "method"]');
    });

    it('throws exception for non-existent controller method', function () {
        $route = Route::get('/test', [TestController::class, 'nonExistentMethod']);
        $route->matches('/test', 'GET');

        $request = new Request();
        expect(fn () => $route->execute($request))
            ->toThrow(RouterException::class, 'Method nonExistentMethod not found on controller ' . TestController::class);
    });

    it('generates URLs with parameters', function () {
        $route = Route::get('/users/{id}/posts/{post}', fn () => 'post');

        expect($route->generateUrl(['id' => 123, 'post' => 456]))
            ->toBe('/users/123/posts/456');
    });

    it('creates PATCH routes', function () {
        $route = Route::patch('/users/1', fn () => 'update user');

        expect($route)
            ->toBeInstanceOf(Route::class)
            ->getMethods()->toBe(['PATCH']);
    });

    it('creates HEAD routes', function () {
        $route = Route::head('/users', fn () => 'head request');

        expect($route)
            ->toBeInstanceOf(Route::class)
            ->getMethods()->toBe(['HEAD']);
    });

    it('creates OPTIONS routes', function () {
        $route = Route::options('/users', fn () => 'options request');

        expect($route)
            ->toBeInstanceOf(Route::class)
            ->getMethods()->toBe(['OPTIONS']);
    });

    it('creates TRACE routes', function () {
        $route = Route::trace('/users', fn () => 'trace request');

        expect($route)
            ->toBeInstanceOf(Route::class)
            ->getMethods()->toBe(['TRACE']);
    });

    it('matches different HTTP methods correctly', function () {
        $patchRoute = Route::patch('/users/1', fn () => 'update');
        $headRoute = Route::head('/users', fn () => 'head');
        $optionsRoute = Route::options('/users', fn () => 'options');
        $traceRoute = Route::trace('/users', fn () => 'trace');

        expect($patchRoute->matches('/users/1', 'PATCH'))->toBeTrue()
            ->and($patchRoute->matches('/users/1', 'POST'))->toBeFalse()
            ->and($headRoute->matches('/users', 'HEAD'))->toBeTrue()
            ->and($headRoute->matches('/users', 'GET'))->toBeFalse()
            ->and($optionsRoute->matches('/users', 'OPTIONS'))->toBeTrue()
            ->and($optionsRoute->matches('/users', 'GET'))->toBeFalse()
            ->and($traceRoute->matches('/users', 'TRACE'))->toBeTrue()
            ->and($traceRoute->matches('/users', 'GET'))->toBeFalse();
    });
});
