<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http\Middleware;
use Sinaesta\Shared\Http\HttpException;
use Sinaesta\Shared\Http\Request;
use Sinaesta\Shared\Http\Response;
final class JsonMiddleware { public function __invoke(Request $request, callable $next): Response { if (in_array($request->method,['POST','PUT','PATCH'],true) && !str_starts_with($request->header('content-type') ?? '', 'application/json')) { throw new HttpException(400,'Content-Type harus application/json.'); } return $next($request); } }
