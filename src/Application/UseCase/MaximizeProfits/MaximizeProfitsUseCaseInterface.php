<?php

declare(strict_types=1);

namespace App\Application\UseCase\MaximizeProfits;

use App\Application\DTO\BookingRequestDTO;
use App\Domain\Exception\InvalidBookingRequestException;

interface MaximizeProfitsUseCaseInterface
{
    /**
     * @param BookingRequestDTO[] $requestsDTO
     * @return array{
     *   request_ids: string[],
     *   total_profit: float,
     *   avg_night: float,
     *   min_night: float,
     *   max_night: float
     * }
     * @throws InvalidBookingRequestException
     */
    public function execute(array $requestsDTO): array;
}
