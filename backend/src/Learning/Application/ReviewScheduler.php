<?php
declare(strict_types=1);
namespace Sinaesta\Learning\Application;

final class ReviewScheduler
{
    public const DEFAULT_INTERVALS = [1, 3, 7, 14, 30, 60];

    public function schedule(int $currentInterval, bool $correct, bool $goodPerformance = false, array $intervals = self::DEFAULT_INTERVALS): int
    {
        sort($intervals); $currentIndex = 0;
        foreach ($intervals as $index => $days) if ($days <= $currentInterval) $currentIndex = $index;
        if (!$correct) return $intervals[max(0, $currentIndex - 1)];
        return $intervals[min(count($intervals) - 1, $currentIndex + ($goodPerformance ? 2 : 1))];
    }
}
