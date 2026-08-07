<?php

declare(strict_types=1);

namespace Sinaesta\Shared\Http;

use RuntimeException;

final class HttpException extends RuntimeException
{
    /** @param array<string,list<string>>|null $errors */
    public function __construct(public readonly int $status, string $message, public readonly ?array $errors = null) { parent::__construct($message); }
}
