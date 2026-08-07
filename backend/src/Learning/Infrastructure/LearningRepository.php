<?php

declare(strict_types=1);

namespace Sinaesta\Learning\Infrastructure;

use PDO;

final readonly class LearningRepository
{
    public function __construct(private PDO $pdo) {}

    public function recommendationCandidates(int $user, int $limit = 50): array
    {
        $sql = "SELECT q.public_id id,q.difficulty,t.public_id topic_id,t.name topic,c.name category,
            COALESCE(h.attempts_count,0) attempts_count,COALESCE(h.correct_count,0) correct_count,
            COALESCE(h.incorrect_count,0) incorrect_count,
            COALESCE(TIMESTAMPDIFF(DAY,h.last_answered_at,UTC_TIMESTAMP()),90) days_since_answered,
            COALESCE(AVG(TIMESTAMPDIFF(SECOND,a.started_at,aa.answered_at)),0) average_response_seconds
          FROM questions q JOIN question_topics t ON t.id=q.topic_id JOIN question_categories c ON c.id=q.category_id
          LEFT JOIN user_question_histories h ON h.question_id=q.id AND h.user_id=:user
          LEFT JOIN attempt_question_snapshots s ON s.question_id=q.id
          LEFT JOIN attempts a ON a.id=s.attempt_id AND a.user_id=:attempt_user
          LEFT JOIN attempt_answers aa ON aa.attempt_id=a.id AND aa.attempt_question_snapshot_id=s.id
          WHERE q.status='published'
          GROUP BY q.id,q.public_id,q.difficulty,t.public_id,t.name,c.name,h.attempts_count,h.correct_count,h.incorrect_count,h.last_answered_at
          ORDER BY h.incorrect_count DESC,h.last_answered_at ASC LIMIT " . min(100, max(1, $limit));
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['user' => $user, 'attempt_user' => $user]);
        return $statement->fetchAll();
    }

    public function recentAttempts(int $user): array
    {
        $statement = $this->pdo->prepare('SELECT a.public_id id,q.name,q.mode,a.submitted_at,r.percentage_score FROM attempts a JOIN quiz_templates q ON q.id=a.template_id JOIN attempt_results r ON r.attempt_id=a.id WHERE a.user_id=:user ORDER BY a.submitted_at DESC LIMIT 5');
        $statement->execute(['user' => $user]);
        return $statement->fetchAll();
    }
}
