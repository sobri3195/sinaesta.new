<?php

declare(strict_types=1);

namespace Sinaesta\Learning\Application;

/** Deterministic scoring only: answer correctness always remains server-owned. */
final class RecommendationScorer
{
    public function score(array $candidate): array
    {
        $attempts = max(1, (int) ($candidate['attempts_count'] ?? 0));
        $accuracy = (int) ($candidate['correct_count'] ?? 0) / $attempts;
        $wrong = (int) ($candidate['incorrect_count'] ?? 0);
        $days = isset($candidate['days_since_answered']) ? (int) $candidate['days_since_answered'] : 90;
        $seconds = (float) ($candidate['average_response_seconds'] ?? 0);

        $weights = [
            'weakness' => round((1 - $accuracy) * 40, 2),
            'recency' => round(min(20, $days / 3), 2),
            'wrong_answer' => min(30, $wrong * 10),
            'time' => round(min(10, max(0, $seconds - 60) / 12), 2),
        ];

        $reasons = [];
        if ($accuracy < .6) $reasons[] = 'Akurasi pada topik ini masih rendah.';
        if ($wrong >= 2) $reasons[] = 'Soal ini pernah dijawab salah berulang.';
        if ($days >= 30) $reasons[] = 'Topik ini sudah lama tidak dilatih.';
        if ($seconds > 90) $reasons[] = 'Waktu pengerjaan sebelumnya lebih lama dari biasanya.';
        if ($reasons === []) $reasons[] = 'Dipilih untuk menjaga variasi latihan.';

        return ['score' => round(array_sum($weights), 2), 'weights' => $weights, 'reasons' => $reasons];
    }
}
