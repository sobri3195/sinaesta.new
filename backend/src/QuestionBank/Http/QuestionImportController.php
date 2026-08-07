<?php
declare(strict_types=1);
namespace Sinaesta\QuestionBank\Http;
use Sinaesta\QuestionBank\Application\QuestionImportService; use Sinaesta\Shared\Http\Request; use Sinaesta\Shared\Http\Response;
final readonly class QuestionImportController { public function __construct(private QuestionImportService $service){} public function preview(Request $r):Response {return Response::success('Preview import dibuat.',$this->service->preview($r->body,$r->header('x-filename')??'questions.csv',filter_var($r->query['atomic']??true,FILTER_VALIDATE_BOOL),$r->attribute('user'),(string)$r->attribute('request_id')),201);} public function confirm(Request $r):Response{return Response::success('Import dikonfirmasi.',$this->service->confirm((string)$r->attribute('batchId'),$r->attribute('user'),(string)$r->attribute('request_id')));}}
