<?php
declare(strict_types=1);
namespace Sinaesta\Learning\Application;
use Sinaesta\Learning\Infrastructure\LearningRepository;
use Sinaesta\Shared\Http\HttpException;

final readonly class LearningService
{
    private const MODES=['recommended','weak_topic','mastery_builder','wrong_recovery','mixed','difficulty_progression','quick_recovery'];
    public function __construct(private LearningRepository $repository,private RecommendationScorer $scorer){}
    private function entitled(int $user,string $key):void {if(!$this->repository->hasEntitlement($user,$key))throw new HttpException(403,"Fitur ini memerlukan entitlement {$key}.");}
    public function mastery(int $user):array {$this->entitled($user,'mastery_map');$items=$this->repository->mastery($user);$topic=array_values(array_filter($items,fn($i)=>$i['dimension_type']==='topic'));$average=$topic===[]?0:round(array_sum(array_column($topic,'mastery_score'))/count($topic),2);return ['average_mastery_score'=>$average,'items'=>$items,'new_learner'=>$items===[]];}
    public function weakTopics(int $user):array {$this->entitled($user,'advanced_recommendation');return array_map([$this,'explainWeakTopic'],$this->repository->weakTopics($user));}
    public function recommendations(int $user,int $count=15):array {$this->entitled($user,'advanced_recommendation');$rows=array_slice($this->repository->recommendations($user),0,min(30,max(1,$count)));foreach($rows as &$row)$row['reason']=json_decode($row['reason_json'],true)?:[];return ['recommendations'=>$rows,'question_count'=>array_sum(array_column($rows,'recommended_question_count')),'generated_at'=>gmdate(DATE_ATOM)];}
    public function queue(int $user):array {$this->entitled($user,'smart_review_queue');$items=$this->repository->reviewQueue($user);return ['items'=>$items,'summary'=>array_count_values(array_column($items,'bucket')),'snooze_limit'=>2];}
    public function history(int $user,string $period):array {$this->entitled($user,'mastery_map');return ['period'=>$period,'points'=>$this->repository->masteryHistory($user,$period)];}
    public function adaptivePractice(int $user,string $mode,int $count):array {$this->entitled($user,'adaptive_practice');if(!in_array($mode,self::MODES,true))throw new HttpException(422,'Mode adaptive practice tidak valid.');$settings=$this->repository->settings();$size=min(30,max(1,$count?:($settings['recommendation_rules']['default_size']??15)));return ['mode'=>$mode,'questions'=>$this->repository->adaptiveQuestions($user,$mode,$size),'selection_policy'=>['priority_topic','question_history','difficulty','recency','wrong_answer','exposure_limit','entitlement'],'generated_at'=>gmdate(DATE_ATOM)];}
    private function explainWeakTopic(array $row):array {$reasons=[];$attempts=max(1,(int)$row['attempts_count']);$accuracy=round((int)$row['correct_count']/$attempts*100);if($accuracy<60)$reasons[]="Akurasi {$accuracy}% masih rendah.";if((int)$row['repeated_error_count']>0)$reasons[]=$row['repeated_error_count'].' kesalahan berulang terdeteksi.';if((int)$row['unanswered_count']>0)$reasons[]=$row['unanswered_count'].' soal belum terjawab.';if((float)$row['trend']<0)$reasons[]='Performa beberapa sesi terakhir menurun.';if($row['last_practiced_at']===null)$reasons[]='Topik ini belum pernah dilatih.';$row['reason']=$reasons?:['Mastery perlu diperkuat melalui latihan terarah.'];$row['recommended_question_count']=15;return $row;}
    public function settings():array{return $this->repository->settings();}
    public function updateSettings(array $data,int $actor,string $request):array {$allowed=['mastery_weights','mastery_levels','recommendation_rules','review_intervals'];$filtered=array_intersect_key($data,array_flip($allowed));if($filtered===[])throw new HttpException(422,'Tidak ada konfigurasi yang valid.');$this->repository->updateSettings($filtered,$actor,$request);return $this->settings();}
}
