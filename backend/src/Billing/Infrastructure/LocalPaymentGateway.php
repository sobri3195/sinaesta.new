<?php

declare(strict_types=1);

namespace Sinaesta\Billing\Infrastructure;

use Sinaesta\Billing\Domain\PaymentGatewayInterface;

final readonly class LocalPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private string $secret, private string $paymentBaseUrl) {}

    public function createInvoice(array $payload): array
    {
        $reference = 'local_' . bin2hex(random_bytes(12));
        return ['external_id' => $reference, 'payment_url' => rtrim($this->paymentBaseUrl, '/') . '/' . $reference, 'expires_at' => $payload['expires_at']];
    }

    public function getPaymentStatus(string $externalId): array { return ['external_id' => $externalId, 'status' => 'pending']; }
    public function cancelPayment(string $externalId): array { return ['external_id' => $externalId, 'status' => 'cancelled']; }
    public function refundPayment(string $externalId, int $amount): array { return ['external_id' => $externalId, 'amount' => $amount, 'status' => 'pending']; }

    public function verifyWebhook(array $headers, string $rawBody): bool
    {
        $signature = $headers['x-payment-signature'] ?? '';
        $timestamp = $headers['x-payment-timestamp'] ?? '';
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) { return false; }
        return $this->secret !== '' && hash_equals(hash_hmac('sha256', $timestamp . '.' . $rawBody, $this->secret), $signature);
    }
}
