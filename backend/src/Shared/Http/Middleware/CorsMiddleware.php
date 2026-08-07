<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http\Middleware;
use Sinaesta\Shared\Http\Request;
use Sinaesta\Shared\Http\Response;
final class CorsMiddleware { public function __invoke(Request $request, callable $next): Response { $origin=$request->header('origin'); $allowed=array_filter(array_map('trim',explode(',',getenv('CORS_ALLOWED_ORIGINS') ?: ''))); $response=$next($request); if ($origin === null || !in_array($origin,$allowed,true)) return $response; return new Response($response->status,$response->payload,[...$response->headers,'Access-Control-Allow-Origin'=>$origin,'Access-Control-Allow-Credentials'=>'true','Vary'=>'Origin']); } }
