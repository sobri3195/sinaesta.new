<?php
declare(strict_types=1);
namespace Sinaesta\Learning\Application;

final class DifficultyProgression
{
    public function distribution(array $recent, int $minimumEvidence = 5): array
    {
        $default = ['easy' => 40, 'medium' => 45, 'hard' => 15];
        if (count($recent) < $minimumEvidence) return $default;
        $accuracy = array_sum(array_map(static fn(array $attempt): int => !empty($attempt['correct']) ? 1 : 0, $recent)) / count($recent);
        $level = $recent[0]['difficulty'] ?? 'easy';
        if ($accuracy < .55) return ['easy' => 60, 'medium' => 35, 'hard' => 5];
        if ($accuracy >= .8 && $level === 'easy') return ['easy' => 25, 'medium' => 60, 'hard' => 15];
        if ($accuracy >= .8 && $level === 'medium') return ['easy' => 15, 'medium' => 45, 'hard' => 40];
        return $default;
    }
}
