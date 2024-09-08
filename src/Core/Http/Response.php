<?php
namespace STS\Core\Http;

class Response {
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $body;
    protected array $cookies = [];
    protected array $middleware = []; // Proprietatea pentru middleware-uri

    public function __construct(string $body = '*', int $statusCode = 200, array $headers = []) {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    // Setează codul de stare HTTP
    public function setStatusCode(int $statusCode): self {
        $this->statusCode = $statusCode;
        return $this;
    }

    // Obține codul de stare HTTP
    public function getStatusCode(): int {
        return $this->statusCode;
    }

    // Setează antetele HTTP
    public function setHeader(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    protected function sendHeaders(): self
    {
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        return $this;
    }    

    // Obține antetele HTTP
    public function getHeaders(): array {
        return $this->headers;
    }

    // Setează corpul răspunsului
    public function setBody(string $body): self {
        $this->body = $body;
        return $this;
    }

    // Obține corpul răspunsului
    public function getBody(): string {
        return $this->body;
    }

    // Setează un cookie
    public function setCookie(string $name, string $value, int $expiry = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): self {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expiry' => $expiry,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly
        ];
        return $this;
    }

    public function addCookie(string $name, string $value, array $options = []): self {
        $defaults = [
            'expiry' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => true,
            'sameSite' => 'None'
        ];

        $cookie = array_merge($defaults, $options);
        $cookie['name'] = $name;
        $cookie['value'] = $value;
        $this->cookies[] = $cookie;
        return $this;
    }
    
    // Trimite toate cookie-urile
    protected function sendCookies(): void
    {
        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                [
                    'expires' => $cookie['expiry'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httpOnly'],
                    'samesite' => 'None'
                ]
            );
        }
    }

    // Trimite un răspuns JSON
    public function json(array $data, int $statusCode = 200): self {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->setBody(json_encode($data));
        return $this;
    }

    public function xml($data, int $statusCode = 200): self {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/xml');
        
        $xml = new \SimpleXMLElement('<root/>');
        array_walk_recursive($data, [$xml, 'addChild']);
        
        $this->setBody($xml->asXML());
        return $this;
    }
    
    public function plain(string $text, int $statusCode = 200): self {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/plain');
        $this->setBody($text);
        return $this;
    }
    
    // Redirecționează către o altă locație
    public function redirect(string $url, int $statusCode = 302): self {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        return $this;
    }

    public function download(string $filePath, string $fileName = null): self {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }
    
        $fileName = $fileName ?? basename($filePath);
        $this->setHeader('Content-Description', 'File Transfer');
        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $this->setHeader('Content-Length', (string)filesize($filePath));
        $this->send(); // Trimit anteturile înainte de descărcare
    
        readfile($filePath); // Citește și trimite fișierul
        exit(); // Oprește execuția după descărcare
    }

    // Metoda pentru adăugarea de middleware-uri
    public function addMiddleware(callable $callback): self {
        $this->middleware[] = $callback;
        return $this;
    }
    
    public function send() {
        // Execută middleware-urile
        foreach ($this->middleware as $callback) {
            call_user_func($callback, $this);
        }
    
        // Setează codul de stare HTTP
        http_response_code($this->statusCode);
    
        // Trimite antetele HTTP
        $this->sendHeaders();
    
        // Trimite cookie-urile
        $this->sendCookies();
    
        // Trimite corpul răspunsului
        echo $this->body;
    }    
}