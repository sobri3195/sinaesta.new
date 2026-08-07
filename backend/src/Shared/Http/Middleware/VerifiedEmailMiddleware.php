<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http\Middleware;
use Sinaesta\Shared\Http\HttpException; use Sinaesta\Shared\Http\Request; use Sinaesta\Shared\Http\Response;
final class VerifiedEmailMiddleware { public function __invoke(Request $request, callable $next): Response { if (($request->attribute('user')['email_verified_at'] ?? null)===null) throw new HttpException(403,'Email belum diverifikasi.'); return $next($request); } }
