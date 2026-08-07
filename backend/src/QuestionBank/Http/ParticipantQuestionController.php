<?php

declare(strict_types=1);

namespace Sinaesta\QuestionBank\Http;

use Sinaesta\QuestionBank\Application\QuestionService;
use Sinaesta\Shared\Http\Request;
use Sinaesta\Shared\Http\Response;

final readonly class ParticipantQuestionController
{
    public function __construct(private QuestionService $service) {}
    public function show(Request $request):Response
    {
        $question=$this->service->participant((string)$request->attribute('questionId'));
        return Response::success('Soal.',ParticipantQuestionResource::from($question));
    }
}
