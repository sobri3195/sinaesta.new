<?php

declare(strict_types=1);

namespace Sinaesta\Billing\Domain;

interface PaymentGatewayInterface
{
    public function createInvoice(array $payload): array;
    public function getPaymentStatus(string $externalId): array;
    public function verifyWebhook(array $headers, string $rawBody): bool;
    public function cancelPayment(string $externalId): array;
    public function refundPayment(string $externalId, int $amount): array;
}
