<?php
namespace STS\Core\Http\Middlewares;

use STS\Core\Http\Request as RequestInterface;
use STS\Core\Http\Response as ResponseInterface;
use STS\Core\Http\Middlewares\MiddlewareInterface;

class CoreMiddleware implements MiddlewareInterface {
    /**
     * Handle the incoming request.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $response = $this->before($request);

        if ($response) {
            return $response;
        }

        $response = $next($request);

        // Call the next middleware or controller action
        return $this->after($request, $response);
    }

    /**
     * Terminate the middleware process.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function terminate(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Your middleware termination logic here

        return $response;
    }

    /**
     * Handle errors encountered during the request process.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param \Exception $exception
     * @return ResponseInterface
     */
    public function error(RequestInterface $request, ResponseInterface $response, \Exception $exception): ResponseInterface
    {
        // Logica de gestionare a erorilor
        $this->logError($exception);

        // Returnează un răspuns JSON cu eroare
        return $response->withJson(['error' => 'An error occurred'], 500);
    }

    /**
     * Perform any logic before the main request handling.
     *
     * @param RequestInterface $request
     * @return ResponseInterface|null
     */
    public function before(RequestInterface $request): ?ResponseInterface
    {
        // Your middleware before logic here

        return $response;
    }

    /**
     * Perform any logic after the main request handling.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function after(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Your middleware after logic here

        return $response;
    }

    /**
     * Log the exception details.
     *
     * @param \Exception $exception
     */
    protected function logError(\Exception $exception): void
    {
        // Logica pentru logarea detaliilor excepției
        error_log($exception->getMessage(), 3, storage_path('logs', 'error.log'));
    }
}