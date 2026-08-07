<?php

declare(strict_types=1);

namespace Sinaesta\Shared\Http;

final readonly class Response
{
    /** @param array<string,string> $headers */
    public function __construct(public int $status, public ?array $payload = null, public array $headers = []) {}

    public static function success(string $message, mixed $data = null, int $status = 200, ?array $meta = []): self
    {
        if ($status === 204) { return new self(204); }
        return new self($status, ['success' => true, 'message' => $message, 'data' => $data ?? (object) [], 'meta' => $meta ?? (object) [], 'errors' => null]);
    }

    /** @param array<string,list<string>>|null $errors */
    public static function error(string $message, int $status, ?array $errors = null): self
    {
        return new self($status, ['success' => false, 'message' => $message, 'data' => null, 'meta' => null, 'errors' => $errors]);
    }

    public function send(string $requestId): void
    {
        http_response_code($this->status);
        header('X-Request-Id: ' . $requestId);
        foreach ($this->headers as $key => $value) { header($key . ': ' . $value); }
        if ($this->status === 204) { return; }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([...($this->payload ?? []), 'request_id' => $requestId], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
