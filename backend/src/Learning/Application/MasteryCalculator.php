<?php

declare(strict_types=1);

namespace Sinaesta\Learning\Application;

/** Pure, deterministic adaptive rules. Configuration is loaded by the service, never supplied by clients. */
final class MasteryCalculator
{
    public const DEFAULT_WEIGHTS = [
        'accuracy' => 0.38, 'difficulty' => 0.14, 'evidence' => 0.12, 'recency' => 0.10,
        'response_time' => 0.08, 'confidence' => 0.05, 'consistency' => 0.13, 'repeated_error_penalty' => 4.0,
    ];

    public const DEFAULT_LEVELS = [
        ['min' => 0, 'max' => 39, 'key' => 'needs_review', 'label' => 'Needs Review'],
        ['min' => 40, 'max' => 59, 'key' => 'developing', 'label' => 'Developing'],
        ['min' => 60, 'max' => 74, 'key' => 'intermediate', 'label' => 'Intermediate'],
        ['min' => 75, 'max' => 89, 'key' => 'strong', 'label' => 'Strong'],
        ['min' => 90, 'max' => 100, 'key' => 'mastered', 'label' => 'Mastered'],
    ];

    public function calculate(array $metrics, array $weights = self::DEFAULT_WEIGHTS): float
    {
        $attempts = max(0, (int) ($metrics['attempts'] ?? 0));
        if ($attempts === 0) return 0.0;
        $accuracy = $this->clamp((float) ($metrics['accuracy'] ?? 0));
        $difficulty = $this->clamp((float) ($metrics['difficulty_factor'] ?? .5));
        $evidence = min(1, log(1 + $attempts) / log(21));
        $days = max(0, (int) ($metrics['days_since_practice'] ?? 90));
        $recency = exp(-$days / 30);
        $expectedSeconds = max(1, (float) ($metrics['expected_seconds'] ?? 90));
        $speed = $this->clamp($expectedSeconds / max(1, (float) ($metrics['average_seconds'] ?? $expectedSeconds)));
        $confidence = $this->clamp((float) ($metrics['confidence'] ?? .5));
        $consistency = $this->clamp((float) ($metrics['consistency'] ?? .5));
        $repeated = max(0, (int) ($metrics['repeated_errors'] ?? 0));
        $base = 100 * ($accuracy * $weights['accuracy'] + $difficulty * $weights['difficulty'] + $evidence * $weights['evidence']
            + $recency * $weights['recency'] + $speed * $weights['response_time'] + $confidence * $weights['confidence']
            + $consistency * $weights['consistency']);
        return round(max(0, min(100, $base - $repeated * $weights['repeated_error_penalty'])), 2);
    }

    public function level(float $score, array $levels = self::DEFAULT_LEVELS): array
    {
        foreach ($levels as $level) if ($score >= $level['min'] && $score <= $level['max']) return $level;
        return $levels[0];
    }

    private function clamp(float $value): float { return max(0, min(1, $value)); }
}
