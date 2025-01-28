<?php

declare(strict_types=1);

use Excalibur\Router\RouteCompiler;

describe('RouteCompiler', function () {
    it('compiles basic routes', function () {
        $compiler = new RouteCompiler();
        $pattern = $compiler->compile('/users');

        expect($pattern)->toBe('#^/users$#');
    });

    it('compiles routes with parameters', function () {
        $compiler = new RouteCompiler();
        $pattern = $compiler->compile('/users/{id}');

        expect(preg_match($pattern, '/users/123', $matches))->toBe(1)
            ->and($matches['id'])->toBe('123');
    });

    it('handles custom patterns', function () {
        $compiler = new RouteCompiler();
        $compiler->addPattern('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

        $pattern = $compiler->compile('/users/{id:uuid}');
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        expect(preg_match($pattern, "/users/$uuid", $matches))->toBe(1)
            ->and($matches['id'])->toBe($uuid);
    });

    it('extracts parameters correctly', function () {
        $compiler = new RouteCompiler();
        $matches = [
            0 => '/users/123',
            'id' => '123',
            1 => '123'
        ];

        $parameters = $compiler->extractParameters('/users/{id}', $matches);

        expect($parameters)->toBe(['id' => '123']);
    });

    it('handles multiple parameters', function () {
        $compiler = new RouteCompiler();
        $pattern = $compiler->compile('/users/{id}/posts/{post}');

        expect(preg_match($pattern, '/users/123/posts/456', $matches))->toBe(1)
            ->and($matches['id'])->toBe('123')
            ->and($matches['post'])->toBe('456');
    });

    it('handles custom patterns with constraints', function () {
        $compiler = new RouteCompiler();
        $pattern = $compiler->compile('/users/{id}', ['id' => '[0-9]+']);

        expect(preg_match($pattern, '/users/123', $matches))->toBe(1)
            ->and(preg_match($pattern, '/users/abc', $matches))->toBe(0);
    });
});
