<?php

declare(strict_types=1);

namespace Sinaesta\Shared\Http;

final class Request
{
    /** @param array<string,string> $headers @param array<string,mixed> $query @param array<string,string> $cookies */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $headers = [],
        public readonly array $query = [],
        public readonly array $cookies = [],
        public readonly string $body = '',
        public readonly string $ip = '',
        private array $attributes = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        return new self(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), is_string($path) ? $path : '/', $headers, $_GET, $_COOKIE, file_get_contents('php://input') ?: '', (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    /** @return array<string,mixed> */
    public function json(): array
    {
        if ($this->body === '') {
            return [];
        }
        $data = json_decode($this->body, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \JsonException('JSON body must be an object.');
        }
        return $data;
    }

    public function header(string $name): ?string { return $this->headers[strtolower($name)] ?? null; }
    public function attribute(string $name, mixed $default = null): mixed { return $this->attributes[$name] ?? $default; }
    public function withAttribute(string $name, mixed $value): self { $copy = clone $this; $copy->attributes[$name] = $value; return $copy; }
}
