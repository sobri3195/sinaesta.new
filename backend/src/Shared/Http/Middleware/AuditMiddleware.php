<?php
declare(strict_types=1);
namespace Sinaesta\Shared\Http\Middleware;
use PDO; use Sinaesta\Shared\Http\Request; use Sinaesta\Shared\Http\Response;
final readonly class AuditMiddleware { public function __construct(private PDO $pdo, private string $action) {} public function __invoke(Request $request, callable $next): Response { $response=$next($request); $user=$request->attribute('user'); $stmt=$this->pdo->prepare('INSERT INTO audit_logs (public_id, actor_user_id, action, outcome, request_id, created_at) VALUES (:id,:actor,:action,:outcome,:request,NOW())'); $stmt->execute(['id'=>bin2hex(random_bytes(16)),'actor'=>$user['internal_id'] ?? null,'action'=>$this->action,'outcome'=>$response->status<400?'success':'failure','request'=>$request->attribute('request_id')]); return $response; } }
