<?php

declare(strict_types=1);

namespace App\Application\UseCase\CalculateStats;

use App\Application\DTO\BookingRequestDTO;
use App\Domain\Exception\InvalidBookingRequestException;
use App\Domain\ValueObject\ProfitStats;

interface CalculateStatsUseCaseInterface
{
    /**
     * @param BookingRequestDTO[] $requestsDTO
     * @throws InvalidBookingRequestException
     */
    public function execute(array $requestsDTO): ProfitStats;
}
