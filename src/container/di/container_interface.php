<?php
namespace sts\container;

/**
 * Interfață ContainerInterface
 * 
 * Definește contractul pentru containerul de servicii.
 */
interface container_interface
{
    public static function getInstance(): self;
    public function bind(string $abstract, $concrete, bool $shared = false, bool $lazy = false, array $tags = [], ?string $alias = null): void;
    public function singleton(string $abstract, $concrete): void;
    public function get(string $abstract);
    public function has(string $abstract): bool;
    public function instance(string $abstract, $instance): void;
    public function forget(string $abstract): void;
    public function alias(string $alias, string $abstract): void;
    public function registerMiddleware(string $name, MiddlewareInterface $middleware): void;
    public function handleMiddleware(RequestInterface $request, callable $finalHandler): ResponseInterface;
    public function on(string $event, callable $listener): void;
    public function off(string $event, callable $listener): void;
    public function call(callable $callback);
}