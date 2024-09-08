<?php
namespace STS\Core\Http;

use STS\Core\Http\Response as ResponseInterface;

class Request {
    public function __construct(
        protected array $get, 
        protected array $post, 
        protected array $server, 
        protected array $headers,
        protected array $sessions
    ) {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;
        $this->headers = $headers;
        $this->sessions = $sessions;
    }

    public static function collection(): self {
        $get = $_GET;
        $post = $_POST;
        $server = $_SERVER;
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $sessions = session_status() === PHP_SESSION_ACTIVE ? $_SESSION : [];

        return new self($get, $post, $server, $headers, $sessions);
    }

    // Funcții de acces pentru a obține datele din cerere
    public function get(?string $key = null, $default = null) {
        return $key === null ? $this->get : ($this->get[$key] ?? $default);
    }

    public function post(?string $key = null, $default = null) {
        return $key === null ? $this->post : ($this->post[$key] ?? $default);
    }

    public function server(?string $key = null, $default = null) {
        return $key === null ? $this->server : ($this->server[$key] ?? $default);
    }

    public function header(?string $key = null, $default = null) {
        $key = strtolower($key);
        $headers = array_change_key_case($this->headers, CASE_LOWER);
        return $key === null ? $this->headers : ($headers[$key] ?? $default);
    }

    public function session(?string $key = null, $default = null) {
        return $key === null ? $this->sessions : ($this->sessions[$key] ?? $default);
    }

    // Alte metode utile, precum method(), uri(), etc.
    public function method(string $type = 'GET'): string {
        return $this->server('REQUEST_METHOD', strtoupper($type) ?? 'GET');
    }

    public function uri(): string {
        return $this->server('REQUEST_URI', '/');
    }

    public function isAjax(): bool {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function has(string $key): bool {
        return isset($this->get[$key]) || isset($this->post[$key]) || isset($this->server[$key]);
    }

    public function input(string $key = null, $default = null) {
        $contentType = $this->header('Content-Type', '');
    
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            $data = $this->post;
        }
    
        return $key === null ? $data : ($data[$key] ?? $default);
    }
    
    // Returnează toate datele din cerere
    public function all(): array {
        return [
            'get' => $this->get,
            'post' => $this->post,
            'server' => $this->server,
            'headers' => $this->headers,
        ];
    }

    public function withRedirect(): ResponseInterface {
        return (new RedirectResponse())->redirect('/');
    }
}