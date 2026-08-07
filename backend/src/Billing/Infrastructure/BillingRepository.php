<?php

declare(strict_types=1);

namespace Sinaesta\Billing\Infrastructure;

use PDO;

final readonly class BillingRepository
{
    public function __construct(private PDO $pdo) {}
    public function pdo(): PDO { return $this->pdo; }

    public function plans(?string $publicId = null): array
    {
        $where = $publicId === null ? '' : ' AND p.public_id=:id';
        $sql = "SELECT p.id internal_id,p.public_id id,p.slug,p.name,p.description,p.plan_type,p.duration_days,p.is_featured,pp.public_id price_id,pp.amount,pp.currency,pp.billing_type FROM subscription_plans p LEFT JOIN plan_prices pp ON pp.plan_id=p.id AND pp.is_active=1 AND pp.valid_from<=UTC_TIMESTAMP() AND (pp.valid_until IS NULL OR pp.valid_until>UTC_TIMESTAMP()) WHERE p.is_active=1{$where} ORDER BY p.sort_order,pp.amount";
        $statement = $this->pdo->prepare($sql); $statement->execute($publicId === null ? [] : ['id' => $publicId]);
        $plans = [];
        foreach ($statement->fetchAll() as $row) {
            $id = $row['id'];
            if (!isset($plans[$id])) { $plans[$id] = ['id'=>$id,'slug'=>$row['slug'],'name'=>$row['name'],'description'=>$row['description'],'type'=>$row['plan_type'],'duration_days'=>$row['duration_days'] === null ? null : (int)$row['duration_days'],'is_featured'=>(bool)$row['is_featured'],'prices'=>[],'features'=>$this->features((int)$row['internal_id'])]; }
            if ($row['price_id'] !== null) { $plans[$id]['prices'][]=['id'=>$row['price_id'],'amount'=>(int)$row['amount'],'currency'=>$row['currency'],'billing_type'=>$row['billing_type']]; }
        }
        return array_values($plans);
    }

    private function features(int $planId): array
    {
        $s=$this->pdo->prepare('SELECT f.entitlement_key,f.name,v.value_type,v.integer_value,v.boolean_value,v.string_value FROM plan_feature_values v JOIN plan_features f ON f.id=v.feature_id WHERE v.plan_id=:plan ORDER BY f.sort_order');$s->execute(['plan'=>$planId]);
        return array_map(static function(array $r): array { $value=match($r['value_type']){'integer'=>(int)$r['integer_value'],'boolean'=>(bool)$r['boolean_value'],default=>$r['string_value']};return ['key'=>$r['entitlement_key'],'name'=>$r['name'],'value'=>$value];},$s->fetchAll());
    }

    public function priceForCheckout(string $planId, ?string $priceId): ?array
    {
        $sql='SELECT p.id plan_id,p.public_id plan_public_id,p.name,p.plan_type,p.duration_days,pp.id price_id,pp.public_id price_public_id,pp.amount,pp.currency FROM subscription_plans p JOIN plan_prices pp ON pp.plan_id=p.id WHERE p.public_id=:plan AND p.is_active=1 AND pp.is_active=1 AND pp.valid_from<=UTC_TIMESTAMP() AND (pp.valid_until IS NULL OR pp.valid_until>UTC_TIMESTAMP()) AND (:price IS NULL OR pp.public_id=:price) ORDER BY pp.amount LIMIT 1';
        $s=$this->pdo->prepare($sql);$s->execute(['plan'=>$planId,'price'=>$priceId]);$r=$s->fetch();return $r ?: null;
    }

    public function validVoucher(string $code, int $planId, int $userId, int $subtotal): ?array
    {
        $sql='SELECT v.id,v.public_id,v.discount_type,v.discount_value,v.max_discount_amount FROM vouchers v WHERE v.code_hash=:code AND v.status=\'active\' AND v.starts_at<=UTC_TIMESTAMP() AND v.expires_at>UTC_TIMESTAMP() AND (v.usage_limit IS NULL OR v.used_count<v.usage_limit) AND (v.per_user_limit IS NULL OR (SELECT COUNT(*) FROM voucher_usages vu WHERE vu.voucher_id=v.id AND vu.user_id=:user)<v.per_user_limit) AND (NOT EXISTS (SELECT 1 FROM voucher_rules vr WHERE vr.voucher_id=v.id) OR EXISTS (SELECT 1 FROM voucher_rules vr WHERE vr.voucher_id=v.id AND (vr.plan_id IS NULL OR vr.plan_id=:plan) AND (vr.minimum_subtotal IS NULL OR vr.minimum_subtotal<=:subtotal))) LIMIT 1';
        $s=$this->pdo->prepare($sql);$s->execute(['plan'=>$planId,'code'=>hash('sha256',strtoupper(trim($code))),'user'=>$userId,'subtotal'=>$subtotal]);$r=$s->fetch();return $r ?: null;
    }

    public function transactionForUser(string $id, int $userId): ?array
    {
        $s=$this->pdo->prepare('SELECT t.id internal_id,t.public_id id,t.status,t.subtotal,t.discount_amount,t.total,t.currency,t.gateway,t.gateway_reference,t.expires_at,t.paid_at,t.created_at,i.public_id invoice_id,i.payment_url FROM transactions t LEFT JOIN invoices i ON i.transaction_id=t.id WHERE t.public_id=:id AND t.user_id=:user LIMIT 1');$s->execute(['id'=>$id,'user'=>$userId]);$r=$s->fetch();return $r ?: null;
    }
    public function transactions(int $userId): array { $s=$this->pdo->prepare('SELECT public_id id,status,subtotal,discount_amount,total,currency,gateway,expires_at,paid_at,created_at FROM transactions WHERE user_id=:user ORDER BY created_at DESC LIMIT 100');$s->execute(['user'=>$userId]);return $s->fetchAll(); }
    public function invoice(string $id,int $userId): ?array { $s=$this->pdo->prepare('SELECT i.public_id id,i.status,i.payment_url,i.expires_at,i.created_at,t.public_id transaction_id,t.total,t.currency FROM invoices i JOIN transactions t ON t.id=i.transaction_id WHERE i.public_id=:id AND t.user_id=:user LIMIT 1');$s->execute(['id'=>$id,'user'=>$userId]);$r=$s->fetch();return $r ?: null; }
    public function subscription(int $userId): ?array { $s=$this->pdo->prepare('SELECT s.public_id id,p.name plan,s.status,s.starts_at,s.expires_at,s.auto_renew FROM subscriptions s JOIN subscription_plans p ON p.id=s.plan_id WHERE s.user_id=:user AND s.status=\'active\' AND (s.expires_at IS NULL OR s.expires_at>UTC_TIMESTAMP()) ORDER BY s.starts_at DESC LIMIT 1');$s->execute(['user'=>$userId]);$r=$s->fetch();return $r ?: null; }
    public function entitlements(int $userId): array { $s=$this->pdo->prepare('SELECT entitlement_key,value_type,integer_value,boolean_value,string_value,starts_at,expires_at FROM user_entitlements WHERE user_id=:user AND revoked_at IS NULL AND starts_at<=UTC_TIMESTAMP() AND (expires_at IS NULL OR expires_at>UTC_TIMESTAMP()) ORDER BY entitlement_key');$s->execute(['user'=>$userId]);return $s->fetchAll(); }
    public function usage(int $userId): array { $s=$this->pdo->prepare('SELECT entitlement_key,period_start,period_end,used_amount,updated_at FROM entitlement_usage WHERE user_id=:user AND period_end>UTC_TIMESTAMP() ORDER BY entitlement_key');$s->execute(['user'=>$userId]);return $s->fetchAll(); }
}
