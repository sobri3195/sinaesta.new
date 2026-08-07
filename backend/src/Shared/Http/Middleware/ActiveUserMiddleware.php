<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http\Middleware;
use Sinaesta\Shared\Http\HttpException; use Sinaesta\Shared\Http\Request; use Sinaesta\Shared\Http\Response;
final class ActiveUserMiddleware { public function __invoke(Request $request, callable $next): Response { if (($request->attribute('user')['status'] ?? null)!=='active') throw new HttpException(403,'Akun tidak aktif.'); return $next($request); } }
