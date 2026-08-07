<?php

declare(strict_types=1);

use Sinaesta\Identity\Application\AuthService;
use Sinaesta\Billing\Application\BillingService;
use Sinaesta\Billing\Http\BillingController;
use Sinaesta\Billing\Infrastructure\BillingRepository;
use Sinaesta\Billing\Infrastructure\LocalPaymentGateway;
use Sinaesta\Billing\Infrastructure\PaymentGatewayRegistry;
use Sinaesta\Assessment\Application\AssessmentService;
use Sinaesta\Assessment\Application\ScoringService;
use Sinaesta\Assessment\Http\AssessmentController;
use Sinaesta\Assessment\Infrastructure\AssessmentRepository;
use Sinaesta\Identity\Http\AuthController;
use Sinaesta\Identity\Infrastructure\AuthRepository;
use Sinaesta\Learning\Application\LearningService;
use Sinaesta\Learning\Application\RecommendationScorer;
use Sinaesta\Learning\Http\LearningController;
use Sinaesta\Learning\Infrastructure\LearningRepository;
use Sinaesta\QuestionBank\Application\QuestionService;
use Sinaesta\QuestionBank\Application\QuestionImportService;
use Sinaesta\QuestionBank\Application\QuestionValidator;
use Sinaesta\QuestionBank\Http\QuestionController;
use Sinaesta\QuestionBank\Http\ParticipantQuestionController;
use Sinaesta\QuestionBank\Http\QuestionImportController;
use Sinaesta\QuestionBank\Infrastructure\QuestionRepository;
use Sinaesta\Shared\Http\HealthController;
use Sinaesta\Shared\Http\HttpException;
use Sinaesta\Shared\Http\Middleware\ActiveUserMiddleware;
use Sinaesta\Shared\Http\Middleware\AuthenticationMiddleware;
use Sinaesta\Shared\Http\Middleware\CorsMiddleware;
use Sinaesta\Shared\Http\Middleware\CsrfMiddleware;
use Sinaesta\Shared\Http\Middleware\JsonMiddleware;
use Sinaesta\Shared\Http\Middleware\RateLimitMiddleware;
use Sinaesta\Shared\Http\Middleware\RoleMiddleware;
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
    $questionService = new QuestionService(new QuestionRepository($pdo), new QuestionValidator());
    $questions = new QuestionController($questionService);
    $participantQuestions = new ParticipantQuestionController($questionService);
    $questionImports = new QuestionImportController(new QuestionImportService($pdo, new QuestionRepository($pdo), new QuestionValidator()));
    $assessments = new AssessmentController(new AssessmentService(new AssessmentRepository($pdo), new ScoringService()));
    $learning = new LearningController(new LearningService(new LearningRepository($pdo), new RecommendationScorer()));
    $billingRepository = new BillingRepository($pdo);
    $billing = new BillingController(new BillingService($billingRepository, new PaymentGatewayRegistry([
        'local' => new LocalPaymentGateway((string) getenv('PAYMENT_WEBHOOK_SECRET'), (string) (getenv('PAYMENT_BASE_URL') ?: 'https://payments.example.test/pay')),
    ])), $billingRepository);
    $router = new Router();

    $router->group('/api/v1', static function (Router $router) use ($controller, $questions, $questionImports, $participantQuestions, $assessments, $learning, $billing, $health, $auth, $active, $csrf, $json, $pdo): void {
        $router->get('/health', [$health, 'health']);
        $router->get('/health/live', [$health, 'live']);
        $router->get('/health/ready', [$health, 'ready']);
        $router->get('/packages', [$billing, 'packages']);
        $router->get('/packages/{packageId}', [$billing, 'package']);
        $router->post('/checkout', [$billing, 'checkout'], [$json,$auth,$active,$csrf]);
        $router->get('/transactions', [$billing, 'transactions'], [$auth,$active]);
        $router->get('/transactions/{transactionId}', [$billing, 'transaction'], [$auth,$active]);
        $router->get('/invoices/{invoiceId}', [$billing, 'invoice'], [$auth,$active]);
        $router->get('/me/subscription', [$billing, 'subscription'], [$auth,$active]);
        $router->get('/me/entitlements', [$billing, 'entitlements'], [$auth,$active]);
        $router->get('/me/usage', [$billing, 'usage'], [$auth,$active]);
        $router->post('/payments/webhook/{gateway}', [$billing, 'webhook']);
        $router->get('/questions/{questionId}', [$participantQuestions, 'show'], [$auth, $active]);
        $read=[$auth,$active];$write=[$json,$auth,$active,$csrf];
        $router->get('/practice/config',[$assessments,'config'],$read);
        $router->post('/practice/start',[$assessments,'startPractice'],$write);
        $router->get('/tryouts',[$assessments,'tryouts'],$read);
        $router->get('/tryouts/{tryoutId}',[$assessments,'tryout'],$read);
        $router->post('/tryouts/{tryoutId}/start',[$assessments,'startTryout'],$write);
        $router->get('/attempts/{attemptId}',[$assessments,'attempt'],$read);
        $router->put('/attempts/{attemptId}/answers/{questionId}',[$assessments,'answer'],$write);
        $router->post('/attempts/{attemptId}/flags/{questionId}',[$assessments,'addFlag'],$write);
        $router->delete('/attempts/{attemptId}/flags/{questionId}',[$assessments,'removeFlag'],[$auth,$active,$csrf]);
        $router->post('/attempts/{attemptId}/submit',[$assessments,'submit'],$write);
        $router->get('/attempts/{attemptId}/result',[$assessments,'result'],$read);
        $router->get('/attempts/{attemptId}/review',[$assessments,'review'],$read);
        $router->get('/me/attempts',[$assessments,'mine'],$read);
        $router->get('/me/analytics',[$assessments,'analytics'],$read);
        $router->get('/me/wrong-questions',[$assessments,'wrong'],$read);
        $router->get('/me/bookmarks',[$assessments,'bookmarks'],$read);
        $router->get('/me/practice-recommendations',[$learning,'recommendations'],$read);
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
        $router->group('/admin/questions', static function (Router $router) use ($questions, $auth, $active, $csrf, $json): void {
            $read = [$auth, $active];
            $write = [$json, $auth, $active, $csrf];
            $router->get('', [$questions, 'index'], $read);
            $router->post('', [$questions, 'create'], $write);
            $router->get('/{questionId}', [$questions, 'show'], $read);
            $router->patch('/{questionId}', [$questions, 'update'], $write);
            $router->delete('/{questionId}', [$questions, 'delete'], [$auth, $active, $csrf]);
            $router->post('/{questionId}/submit-review', [$questions, 'submitReview'], $write);
            $router->post('/{questionId}/request-revision', [$questions, 'requestRevision'], $write);
            $router->post('/{questionId}/approve', [$questions, 'approve'], $write);
            $router->post('/{questionId}/publish', [$questions, 'publish'], $write);
            $router->post('/{questionId}/archive', [$questions, 'archive'], $write);
            $router->post('/{questionId}/restore', [$questions, 'restore'], $write);
            $router->post('/{questionId}/duplicate', [$questions, 'duplicate'], $write);
            $router->get('/{questionId}/versions', [$questions, 'versions'], $read);
            $router->get('/{questionId}/reviews', [$questions, 'reviews'], $read);
            $router->get('/{questionId}/history', [$questions, 'history'], $read);
        });
        $router->post('/admin/question-imports/preview', [$questionImports, 'preview'], [$auth, $active, new RoleMiddleware('admin'), $csrf]);
        $router->post('/admin/question-imports/{batchId}/confirm', [$questionImports, 'confirm'], [$json, $auth, $active, new RoleMiddleware('admin'), $csrf]);
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
