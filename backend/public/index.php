<?php

declare(strict_types=1);

use Sinaesta\Identity\Application\AuthService;
use Sinaesta\Identity\Http\AuthController;
use Sinaesta\Identity\Infrastructure\AuthRepository;
use Sinaesta\Shared\Http\HealthController;
use Sinaesta\Shared\Http\HttpException;
use Sinaesta\Shared\Http\Middleware\ActiveUserMiddleware;
use Sinaesta\Shared\Http\Middleware\AuthenticationMiddleware;
use Sinaesta\Shared\Http\Middleware\CorsMiddleware;
use Sinaesta\Shared\Http\Middleware\CsrfMiddleware;
use Sinaesta\Shared\Http\Middleware\JsonMiddleware;
use Sinaesta\Shared\Http\Middleware\RateLimitMiddleware;
use Sinaesta\Shared\Http\Request;
use Sinaesta\Shared\Http\Response;
use Sinaesta\Shared\Http\Router;

$requestId = bin2hex(random_bytes(16));

try {
    $pdo = require dirname(__DIR__) . '/config/bootstrap.php';
    $repository = new AuthRepository($pdo);
    $auth = new AuthenticationMiddleware($repository);
    $active = new ActiveUserMiddleware();
    $csrf = new CsrfMiddleware();
    $json = new JsonMiddleware();
    $cors = new CorsMiddleware();
    $controller = new AuthController(new AuthService($repository), $repository);
    $health = new HealthController($pdo);
    $router = new Router();

    $router->group('/api/v1', static function (Router $router) use ($controller, $health, $auth, $active, $csrf, $json, $pdo): void {
        $router->get('/health', [$health, 'health']);
        $router->get('/health/live', [$health, 'live']);
        $router->get('/health/ready', [$health, 'ready']);
        $router->group('/auth', static function (Router $router) use ($controller, $auth, $active, $csrf, $json, $pdo): void {
            $router->post('/register', [$controller, 'register'], [$json]);
            $router->post('/login', [$controller, 'login'], [$json, new RateLimitMiddleware($pdo, 'login', 10, 900)]);
            $router->post('/forgot-password', [$controller, 'forgot'], [$json, new RateLimitMiddleware($pdo, 'password-reset', 5, 3600)]);
            $router->post('/reset-password', [$controller, 'reset'], [$json, new RateLimitMiddleware($pdo, 'password-reset-consume', 10, 3600)]);
            $router->get('/email/verify', [$controller, 'verify'], [new RateLimitMiddleware($pdo, 'email-verify', 20, 3600)]);
            $protected = [$auth, $active, $csrf];
            $router->post('/logout', [$controller, 'logout'], $protected);
            $router->post('/logout-all', [$controller, 'logoutAll'], $protected);
            $router->get('/me', [$controller, 'me'], [$auth, $active]);
            $router->post('/change-password', [$controller, 'change'], [$json, ...$protected]);
            $router->post('/email/resend', [$controller, 'resend'], [$json, ...$protected, new RateLimitMiddleware($pdo, 'email-resend', 5, 3600)]);
            $router->get('/sessions', [$controller, 'sessions'], [$auth, $active]);
            $router->delete('/sessions/{sessionId}', [$controller, 'revokeSession'], $protected);
        });
    });

    $request = Request::fromGlobals()->withAttribute('request_id', $requestId);
    $response = $cors($request, static fn(Request $request): Response => $router->dispatch($request));
} catch (HttpException $exception) {
    $response = Response::error($exception->getMessage(), $exception->status, $exception->errors);
} catch (JsonException) {
    $response = Response::error('JSON tidak valid.', 400);
} catch (Throwable $exception) {
    error_log(json_encode(['level' => 'error', 'request_id' => $requestId, 'exception' => get_class($exception), 'message' => $exception->getMessage()]));
    $response = Response::error('Terjadi kesalahan internal.', 500);
}

$response->send($requestId);
