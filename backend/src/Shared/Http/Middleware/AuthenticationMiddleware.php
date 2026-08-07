<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http\Middleware;
use Sinaesta\Identity\Infrastructure\AuthRepository;
use Sinaesta\Shared\Http\HttpException;
use Sinaesta\Shared\Http\Request;
use Sinaesta\Shared\Http\Response;
final readonly class AuthenticationMiddleware { public function __construct(private AuthRepository $repository) {} public function __invoke(Request $request, callable $next): Response { $header=$request->header('authorization'); $token=str_starts_with($header ?? '','Bearer ') ? substr((string)$header,7) : ($request->cookies[getenv('AUTH_COOKIE_NAME') ?: 'sinaesta_session'] ?? null); if (!is_string($token) || $token==='' || strlen($token)>256) throw new HttpException(401,'Autentikasi diperlukan.'); $session=$this->repository->findActiveSession(hash('sha256',$token)); if ($session===null) throw new HttpException(401,'Sesi tidak valid atau telah kedaluwarsa.'); return $next($request->withAttribute('user',$session)->withAttribute('session_token',$token)); } }
