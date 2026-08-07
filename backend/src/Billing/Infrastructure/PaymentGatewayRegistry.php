<?php

declare(strict_types=1);

namespace Sinaesta\Billing\Infrastructure;

use InvalidArgumentException;
use Sinaesta\Billing\Domain\PaymentGatewayInterface;

final readonly class PaymentGatewayRegistry
{
    /** @param array<string,PaymentGatewayInterface> $gateways */
    public function __construct(private array $gateways) {}
    public function get(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) { throw new InvalidArgumentException('Gateway pembayaran tidak didukung.'); }
        return $this->gateways[$name];
    }
}
