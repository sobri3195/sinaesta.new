<?php

declare(strict_types=1);

namespace Sinaesta\QuestionBank\Http;

use Sinaesta\QuestionBank\Application\QuestionService;
use Sinaesta\Shared\Http\Request;
use Sinaesta\Shared\Http\Response;

final readonly class QuestionController
{
    public function __construct(private QuestionService $service) {}
    public function index(Request $request):Response { $result=$this->service->list($request->query); return Response::success('Daftar soal.',$result['items'],200,$result['meta']); }
    public function create(Request $request):Response { return Response::success('Draft soal dibuat.',$this->service->create($request->json(),$request->attribute('user')),201); }
    public function show(Request $request):Response { return Response::success('Detail soal.',$this->service->get($this->id($request))); }
    public function update(Request $request):Response { return Response::success('Soal diperbarui.',$this->service->update($this->id($request),$request->json(),$request->attribute('user'))); }
    public function delete(Request $request):Response { $this->service->transition($this->id($request),'archive',$request->attribute('user'),null); return Response::success('Soal diarsipkan.',null,204); }
    public function submitReview(Request $r):Response { return $this->workflow($r,'submit-review','Soal dikirim untuk review.'); }
    public function requestRevision(Request $r):Response { return $this->workflow($r,'request-revision','Revisi diminta.'); }
    public function approve(Request $r):Response { return $this->workflow($r,'approve','Soal disetujui.'); }
    public function publish(Request $r):Response { return $this->workflow($r,'publish','Soal dipublikasikan.'); }
    public function archive(Request $r):Response { return $this->workflow($r,'archive','Soal diarsipkan.'); }
    public function restore(Request $r):Response { return $this->workflow($r,'restore','Soal dipulihkan sebagai draft.'); }
    public function duplicate(Request $r):Response { return Response::success('Soal diduplikasi.',$this->service->duplicate($this->id($r),$r->attribute('user')),201); }
    public function versions(Request $r):Response { return Response::success('Daftar versi.',$this->service->related($this->id($r),'versions')); }
    public function reviews(Request $r):Response { return Response::success('Daftar review.',$this->service->related($this->id($r),'reviews')); }
    public function history(Request $r):Response { return Response::success('Riwayat status.',$this->service->related($this->id($r),'history')); }
    private function workflow(Request $r,string $action,string $message):Response { $body=$r->json(); return Response::success($message,$this->service->transition($this->id($r),$action,$r->attribute('user'),isset($body['note'])?(string)$body['note']:null)); }
    private function id(Request $request):string { return (string)$request->attribute('questionId'); }
}
