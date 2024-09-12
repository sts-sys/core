<?php
namespace sts\server\middlewares;

use sts\server\Request as RequestInterface;
use sts\server\Response as ResponseInterface;

/**
 * Interface MiddlewareInterface
 * @package sts\server\Middlewares
 */
interface MiddlewareInterface
{
    /**
     * Handle the incoming request.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface;

    /**
     * Terminate the middleware process.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function terminate(RequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * Handle errors encountered during the request process.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param \Throwable $exception
     * @return ResponseInterface
     */
    public function error(RequestInterface $request, ResponseInterface $response, \Throwable $exception): ResponseInterface;

    /**
     * Perform any logic before the main request handling.
     *
     * @param RequestInterface $request
     * @return ResponseInterface|null
     */
    public function before(RequestInterface $request): ?ResponseInterface;

    /**
     * Perform any logic after the main request handling.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function after(RequestInterface $request, ResponseInterface $response): ResponseInterface;
}