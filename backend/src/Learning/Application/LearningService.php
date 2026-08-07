<?php

declare(strict_types=1);

namespace Sinaesta\Learning\Application;

use Sinaesta\Learning\Infrastructure\LearningRepository;

final readonly class LearningService
{
    public function __construct(private LearningRepository $repository, private RecommendationScorer $scorer) {}

    public function recommendations(int $user, int $count = 10): array
    {
        $items = array_map(function (array $row): array {
            $scored = $this->scorer->score($row);
            return [
                'question_id' => $row['id'], 'topic' => ['id' => $row['topic_id'], 'name' => $row['topic']],
                'category' => $row['category'], 'difficulty' => $row['difficulty'],
                'recommendation_score' => $scored['score'], 'reasons' => $scored['reasons'],
            ];
        }, $this->repository->recommendationCandidates($user));
        usort($items, static fn(array $a, array $b): int => $b['recommendation_score'] <=> $a['recommendation_score']);
        $items = array_slice($items, 0, min(30, max(1, $count)));
        $topics = array_count_values(array_column(array_column($items, 'topic'), 'name'));
        arsort($topics);
        return [
            'mode' => 'recommended', 'questions' => $items,
            'question_count' => count($items), 'priority_topics' => array_slice(array_keys($topics), 0, 3),
            'estimated_duration_minutes' => max(1, (int) ceil(count($items) * 1.5)),
            'recent_attempts' => $this->repository->recentAttempts($user),
            'generated_at' => gmdate(DATE_ATOM),
        ];
    }
}
