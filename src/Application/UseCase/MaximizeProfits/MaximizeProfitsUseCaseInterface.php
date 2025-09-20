<?php

declare(strict_types=1);

namespace App\Application\UseCase\MaximizeProfits;

use App\Application\DTO\BookingRequestDTO;
use App\Domain\Exception\InvalidBookingRequestException;
use App\Domain\ValueObject\BookingOptimizationResult;

interface MaximizeProfitsUseCaseInterface
{
    /**
     * @param BookingRequestDTO[] $requestsDTO
     * @throws InvalidBookingRequestException
     */
    public function execute(array $requestsDTO): BookingOptimizationResult;
}
