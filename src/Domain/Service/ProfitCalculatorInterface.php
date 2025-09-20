<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\BookingRequest;
use App\Domain\ValueObject\ProfitStats;

interface ProfitCalculatorInterface
{
    /**
     * @param BookingRequest[] $requests
     */
    public function calculateStats(array $requests): ProfitStats;
}
