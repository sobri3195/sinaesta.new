<?php
declare(strict_types=1);
namespace Sinaesta\Learning\Infrastructure;
use PDO;

final readonly class LearningRepository
{
    public function __construct(private PDO $pdo) {}
    public function hasEntitlement(int $user, string $key): bool
    {
        $s=$this->pdo->prepare("SELECT 1 FROM user_entitlements WHERE user_id=:user AND entitlement_key=:entitlement AND boolean_value=1 AND revoked_at IS NULL AND starts_at<=UTC_TIMESTAMP() AND (expires_at IS NULL OR expires_at>UTC_TIMESTAMP()) LIMIT 1");
        $s->execute(['user'=>$user,'entitlement'=>$key]); return (bool)$s->fetchColumn();
    }
    public function settings(): array
    {
        $rows=$this->pdo->query('SELECT setting_key,value_json FROM adaptive_learning_settings')->fetchAll(); $result=[];
        foreach($rows as $row)$result[$row['setting_key']]=json_decode($row['value_json'],true,16,JSON_THROW_ON_ERROR); return $result;
    }
    public function mastery(int $user): array
    {
        $s=$this->pdo->prepare('SELECT dimension_type,dimension_id,dimension_name,mastery_score,mastery_level,attempts_count,correct_count,unanswered_count,repeated_error_count,average_response_seconds,trend,last_practiced_at,calculated_at FROM user_topic_mastery WHERE user_id=:user ORDER BY dimension_type,mastery_score');$s->execute(['user'=>$user]);return $s->fetchAll();
    }
    public function weakTopics(int $user): array
    {
        $s=$this->pdo->prepare("SELECT dimension_id topic_id,dimension_name topic_name,mastery_score,mastery_level,attempts_count,correct_count,unanswered_count,repeated_error_count,average_response_seconds,trend,last_practiced_at,LEAST(100,100-mastery_score+repeated_error_count*5+unanswered_count*2+GREATEST(0,-trend)) priority_score FROM user_topic_mastery WHERE user_id=:user AND dimension_type='topic' ORDER BY priority_score DESC LIMIT 20");$s->execute(['user'=>$user]);return $s->fetchAll();
    }
    public function recommendations(int $user): array
    {
        $s=$this->pdo->prepare("SELECT r.public_id id,t.public_id topic_id,t.name topic_name,r.mode,r.mastery_score,r.priority_score,r.reason_json,r.recommended_question_count,r.created_at FROM adaptive_recommendations r JOIN question_topics t ON t.id=r.topic_id WHERE r.user_id=:user AND r.expires_at>UTC_TIMESTAMP() ORDER BY r.priority_score DESC LIMIT 20");$s->execute(['user'=>$user]);return $s->fetchAll();
    }
    public function reviewQueue(int $user): array
    {
        $s=$this->pdo->prepare("SELECT rq.public_id id,t.name topic,q.public_id question_id,rq.mastery_before,rq.priority,rq.recovery_stage,rq.failure_count,rq.last_reviewed_at,rq.next_review_at,rq.interval_days,rq.snooze_count,CASE WHEN rq.next_review_at<UTC_TIMESTAMP() THEN 'overdue' WHEN DATE(rq.next_review_at)=UTC_DATE() THEN 'due_today' ELSE 'upcoming' END bucket FROM user_review_queue rq JOIN questions q ON q.id=rq.question_id AND q.status='published' JOIN question_topics t ON t.id=rq.topic_id WHERE rq.user_id=:user AND rq.status IN ('due','scheduled','snoozed') ORDER BY rq.next_review_at,rq.priority DESC");$s->execute(['user'=>$user]);return $s->fetchAll();
    }
    public function masteryHistory(int $user,string $period): array
    {
        $days=['7d'=>7,'30d'=>30,'90d'=>90,'all'=>36500][$period]??30;
        $s=$this->pdo->prepare("SELECT DATE(captured_at) date,ROUND(AVG(mastery_score),2) mastery_score FROM mastery_snapshots WHERE user_id=:user AND captured_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL {$days} DAY) GROUP BY DATE(captured_at) ORDER BY date");$s->execute(['user'=>$user]);return $s->fetchAll();
    }
    public function adaptiveQuestions(int $user,string $mode,int $limit): array
    {
        $sql="SELECT q.public_id question_id,t.public_id topic_id,t.name topic_name,q.difficulty,COALESCE(m.mastery_score,0) mastery_score,COALESCE(rq.priority,0) review_priority,COALESCE(h.incorrect_count,0) incorrect_count FROM questions q JOIN question_topics t ON t.id=q.topic_id LEFT JOIN user_topic_mastery m ON m.user_id=:mastery_user AND m.dimension_type='topic' AND m.dimension_id=t.public_id LEFT JOIN user_review_queue rq ON rq.user_id=:queue_user AND rq.question_id=q.id LEFT JOIN user_question_histories h ON h.user_id=:history_user AND h.question_id=q.id WHERE q.status='published' AND COALESCE(h.attempts_count,0)<:exposure ORDER BY (100-COALESCE(m.mastery_score,0))+COALESCE(rq.priority,0)+COALESCE(h.incorrect_count,0)*10 DESC,q.id LIMIT {$limit}";
        $s=$this->pdo->prepare($sql);$s->execute(['mastery_user'=>$user,'queue_user'=>$user,'history_user'=>$user,'exposure'=>3]);return $s->fetchAll();
    }
    public function updateSettings(array $settings,int $actor,string $requestId): void
    {
        $this->pdo->beginTransaction();try{foreach($settings as $key=>$value){$s=$this->pdo->prepare('UPDATE adaptive_learning_settings SET value_json=:value,version=version+1,updated_by=:actor,updated_at=UTC_TIMESTAMP() WHERE setting_key=:key');$s->execute(['value'=>json_encode($value,JSON_THROW_ON_ERROR),'actor'=>$actor,'key'=>$key]);}$a=$this->pdo->prepare("INSERT INTO audit_logs(public_id,actor_user_id,action,outcome,request_id,created_at) VALUES(:id,:actor,'adaptive_learning.settings_updated','success',:request,UTC_TIMESTAMP())");$a->execute(['id'=>bin2hex(random_bytes(16)),'actor'=>$actor,'request'=>$requestId]);$this->pdo->commit();}catch(\Throwable $e){$this->pdo->rollBack();throw $e;}
    }
    public function recommendationCandidates(int $user,int $limit=50): array { return []; }
    public function recentAttempts(int $user): array { return []; }
}
