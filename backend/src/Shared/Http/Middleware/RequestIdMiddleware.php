<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http\Middleware;
use Sinaesta\Shared\Http\Request;
use Sinaesta\Shared\Http\Response;
final class RequestIdMiddleware { public function __invoke(Request $request, callable $next): Response { $id = $request->header('x-request-id'); if ($id === null || preg_match('/^[A-Za-z0-9-]{8,64}$/', $id) !== 1) { $b=random_bytes(16); $b[6]=chr((ord($b[6])&15)|64); $b[8]=chr((ord($b[8])&63)|128); $id=vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b),4)); } return $next($request->withAttribute('request_id',$id)); } }
