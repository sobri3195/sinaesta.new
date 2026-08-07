<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http\Middleware;
use Sinaesta\Shared\Http\HttpException; use Sinaesta\Shared\Http\Request; use Sinaesta\Shared\Http\Response;
final readonly class PermissionMiddleware { public function __construct(private string $permission) {} public function __invoke(Request $request, callable $next): Response { if (!in_array($this->permission,$request->attribute('user')['permissions'] ?? [],true)) throw new HttpException(403,'Akses ditolak.'); return $next($request); } }
