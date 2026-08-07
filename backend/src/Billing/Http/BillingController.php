<?php

declare(strict_types=1);

namespace Sinaesta\Billing\Http;

use Sinaesta\Billing\Application\BillingService;
use Sinaesta\Billing\Infrastructure\BillingRepository;
use Sinaesta\Shared\Http\HttpException;
use Sinaesta\Shared\Http\Request;
use Sinaesta\Shared\Http\Response;

final readonly class BillingController
{
    public function __construct(private BillingService $service,private BillingRepository $repository) {}
    public function packages(Request $r): Response{return Response::success('Daftar paket.',$this->service->packages());}
    public function package(Request $r): Response{return Response::success('Detail paket.',$this->service->packages((string)$r->attribute('packageId')));}
    public function checkout(Request $r): Response{return Response::success('Invoice pembayaran dibuat.',$this->service->checkout($this->userId($r),$r->json()),201);}
    public function transactions(Request $r): Response{return Response::success('Daftar transaksi.',$this->repository->transactions($this->userId($r)));}
    public function transaction(Request $r): Response{$row=$this->repository->transactionForUser((string)$r->attribute('transactionId'),$this->userId($r));if(!$row)throw new HttpException(404,'Transaksi tidak ditemukan.');return Response::success('Detail transaksi.',$row);}
    public function invoice(Request $r): Response{$row=$this->repository->invoice((string)$r->attribute('invoiceId'),$this->userId($r));if(!$row)throw new HttpException(404,'Invoice tidak ditemukan.');return Response::success('Detail invoice.',$row);}
    public function subscription(Request $r): Response{return Response::success('Subscription aktif.',$this->repository->subscription($this->userId($r)));}
    public function entitlements(Request $r): Response{return Response::success('Entitlement aktif.',$this->repository->entitlements($this->userId($r)));}
    public function usage(Request $r): Response{return Response::success('Penggunaan entitlement.',$this->repository->usage($this->userId($r)));}
    public function webhook(Request $r): Response{$this->service->webhook((string)$r->attribute('gateway'),$r->headers,$r->body);return Response::success('Webhook diproses.',null);}
    private function userId(Request $r): int{$user=$r->attribute('user');if(!is_array($user)||!isset($user['internal_id']))throw new HttpException(401,'Autentikasi diperlukan.');return (int)$user['internal_id'];}
}
