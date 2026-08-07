<?php

declare(strict_types=1);

namespace Sinaesta\Learning\Http;

use Sinaesta\Learning\Application\LearningService;
use Sinaesta\Shared\Http\Request;
use Sinaesta\Shared\Http\Response;

final readonly class LearningController
{
    public function __construct(private LearningService $service) {}
    public function recommendations(Request $request): Response
    {
        $count = (int) ($request->query['count'] ?? 10);
        return Response::success('Latihan yang direkomendasikan.', $this->service->recommendations((int) $request->attribute('user')['internal_id'], $count));
    }
}
