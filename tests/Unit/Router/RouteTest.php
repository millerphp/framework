<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Tests\Fixtures\Controllers\TestController;
use Excalibur\Router\Route;
use Excalibur\Router\Exception\RouterException;

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

        expect($route->execute())->toBe('result');
    });

    it('executes controller method handlers', function () {
        $route = Route::get('/test', TestController::class . '@index');

        expect($route->execute())->toBe('controller result');
    });

    it('throws exception for non-existent controller', function () {
        $route = Route::get('/test', 'NonExistentController@index');

        expect(fn () => $route->execute())
            ->toThrow(RouterException::class, 'Controller class NonExistentController not found');
    });

    it('generates URLs with parameters', function () {
        $route = Route::get('/users/{id}/posts/{post}', fn () => 'post');

        expect($route->generateUrl(['id' => 123, 'post' => 456]))
            ->toBe('/users/123/posts/456');
    });
});
