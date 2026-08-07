<?php

declare(strict_types=1);

/** Run every five minutes. Reminders are queued as audit events for the notification worker. */
$pdo = require dirname(__DIR__) . '/config/bootstrap.php';
$pdo->beginTransaction();
try {
    $pdo->exec("UPDATE transactions SET status='expired',updated_at=UTC_TIMESTAMP() WHERE status='pending' AND expires_at<=UTC_TIMESTAMP()");
    $pdo->exec("UPDATE invoices i JOIN transactions t ON t.id=i.transaction_id SET i.status='expired',i.updated_at=UTC_TIMESTAMP() WHERE i.status='pending' AND t.status='expired'");
    $pdo->exec("UPDATE subscriptions SET status='expired',updated_at=UTC_TIMESTAMP() WHERE status='active' AND expires_at IS NOT NULL AND expires_at<=UTC_TIMESTAMP()");
    $pdo->exec("UPDATE user_entitlements SET revoked_at=UTC_TIMESTAMP() WHERE revoked_at IS NULL AND expires_at IS NOT NULL AND expires_at<=UTC_TIMESTAMP()");
    $pdo->exec("UPDATE vouchers SET status='expired',updated_at=UTC_TIMESTAMP() WHERE status='active' AND expires_at<=UTC_TIMESTAMP()");
    $pdo->exec("INSERT INTO tryout_token_transactions(public_id,wallet_id,type,amount,description,created_at) SELECT LOWER(HEX(RANDOM_BYTES(16))),id,'expire',-balance,'Token kedaluwarsa',UTC_TIMESTAMP() FROM tryout_token_wallets WHERE balance>0 AND expires_at<=UTC_TIMESTAMP()");
    $pdo->exec("UPDATE tryout_token_wallets SET balance=0,updated_at=UTC_TIMESTAMP() WHERE balance>0 AND expires_at<=UTC_TIMESTAMP()");
    $reminder=$pdo->prepare("INSERT INTO audit_logs(public_id,actor_user_id,action,outcome,request_id,created_at) SELECT LOWER(HEX(RANDOM_BYTES(16))),user_id,'subscription.expiration_reminder','queued','billing-cron',UTC_TIMESTAMP() FROM subscriptions WHERE status='active' AND expires_at BETWEEN UTC_TIMESTAMP()+INTERVAL 3 DAY AND UTC_TIMESTAMP()+INTERVAL 3 DAY+INTERVAL 5 MINUTE");$reminder->execute();
    $pdo->commit();
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }

// Pending transactions remain authoritative locally; a gateway-specific worker may
// call getPaymentStatus and feed the same verified/idempotent webhook use case.
echo "Billing maintenance completed.\n";
