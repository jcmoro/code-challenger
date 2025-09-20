<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\BookingRequest;
use App\Domain\ValueObject\ProfitStats;

final readonly class ProfitCalculator implements ProfitCalculatorInterface
{
    /**
     * @param BookingRequest[] $requests
     */
    public function calculateStats(array $requests): ProfitStats
    {
        if ($requests === []) {
            return ProfitStats::empty();
        }

        $profitsPerNight = array_map(
            static fn(BookingRequest $request): float => $request->getProfitPerNight(),
            $requests
        );

        return new ProfitStats(
            avgNight: round(array_sum($profitsPerNight) / count($profitsPerNight), 2),
            minNight: round(min($profitsPerNight), 2),
            maxNight: round(max($profitsPerNight), 2)
        );
    }
}
