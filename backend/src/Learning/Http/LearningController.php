<?php
declare(strict_types=1);
namespace Sinaesta\Learning\Http;
use Sinaesta\Learning\Application\LearningService;use Sinaesta\Shared\Http\Request;use Sinaesta\Shared\Http\Response;
final readonly class LearningController
{
 public function __construct(private LearningService $service){}
 private function user(Request $r):int{return(int)$r->attribute('user')['internal_id'];}
 public function mastery(Request $r):Response{return Response::success('Mastery pengguna.',$this->service->mastery($this->user($r)));}
 public function weakTopics(Request $r):Response{return Response::success('Topik prioritas.',$this->service->weakTopics($this->user($r)));}
 public function recommendations(Request $r):Response{return Response::success('Rekomendasi adaptif.',$this->service->recommendations($this->user($r),(int)($r->query['count']??15)));}
 public function queue(Request $r):Response{return Response::success('Antrean review.',$this->service->queue($this->user($r)));}
 public function history(Request $r):Response{return Response::success('Riwayat mastery teragregasi.',$this->service->history($this->user($r),(string)($r->query['period']??'30d')));}
 public function practice(Request $r):Response{return Response::success('Soal adaptive practice.',$this->service->adaptivePractice($this->user($r),(string)($r->query['mode']??'recommended'),(int)($r->query['count']??15)));}
 public function settings(Request $r):Response{return Response::success('Konfigurasi adaptive learning.',$this->service->settings());}
 public function updateSettings(Request $r):Response{return Response::success('Konfigurasi diperbarui.',$this->service->updateSettings($r->json(),$this->user($r),(string)$r->attribute('request_id')));}
}
