<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\BookingRequest;
use App\Domain\ValueObject\BookingOptimizationResult;

interface BookingOptimizerInterface
{
    /**
     * @param BookingRequest[] $requests
     */
    public function findOptimalCombination(array $requests): BookingOptimizationResult;
}
