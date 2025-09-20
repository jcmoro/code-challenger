<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\BookingRequest;

interface BookingOptimizerInterface
{
    /**
     * @param BookingRequest[] $requests
     * @return array{
     *   request_ids: string[],
     *   total_profit: float,
     *   avg_night: float,
     *   min_night: float,
     *   max_night: float
     * }
 */
    public function findOptimalCombination(array $requests): array;
}
