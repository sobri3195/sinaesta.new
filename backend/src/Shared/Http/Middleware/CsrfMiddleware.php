<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http\Middleware;
use Sinaesta\Shared\Http\HttpException; use Sinaesta\Shared\Http\Request; use Sinaesta\Shared\Http\Response;
final class CsrfMiddleware { public function __invoke(Request $request, callable $next): Response { $cookieName=getenv('AUTH_COOKIE_NAME') ?: 'sinaesta_session'; if (isset($request->cookies[$cookieName]) && in_array($request->method,['POST','PUT','PATCH','DELETE'],true)) { $csrfCookie=$request->cookies['sinaesta_csrf'] ?? ''; $csrfHeader=$request->header('x-csrf-token') ?? ''; if ($csrfCookie==='' || $csrfHeader==='' || !hash_equals($csrfCookie,$csrfHeader)) throw new HttpException(403,'Token CSRF tidak valid.'); } return $next($request); } }
