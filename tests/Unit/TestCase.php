<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\DTO\BookingRequestDTO;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

abstract class TestCase extends BaseTestCase
{
    /**
     * Helper method to create BookingRequestDTO instances for testing
     */
    protected function createBookingRequestDTO(
        string $requestId = 'test_booking',
        string $checkIn = '2020-01-01',
        int $nights = 1,
        float $sellingRate = 100.0,
        float $margin = 20.0
    ): BookingRequestDTO {
        $dto = new BookingRequestDTO();
        $dto->requestId = $requestId;
        $dto->checkIn = $checkIn;
        $dto->nights = $nights;
        $dto->sellingRate = $sellingRate;
        $dto->margin = $margin;

        return $dto;
    }

    /**
     * Helper method to create multiple BookingRequestDTO instances
     *
     * @return BookingRequestDTO[]
     */
    protected function createMultipleBookingRequestDTOs(array $bookings): array
    {
        return array_map(
            fn(array $booking): BookingRequestDTO => $this->createBookingRequestDTO(
                $booking['request_id'] ?? 'default_id',
                $booking['check_in'] ?? '2020-01-01',
                $booking['nights'] ?? 1,
                $booking['selling_rate'] ?? 100.0,
                $booking['margin'] ?? 20.0
            ),
            $bookings
        );
    }

    /**
     * Helper to build a ConstraintViolation instance
     */
    protected function createConstraintViolation(
        string $message,
        string $propertyPath = 'property',
        mixed $invalidValue = null,
        mixed $root = null
    ): ConstraintViolation {
        return new ConstraintViolation(
            message: $message,
            messageTemplate: $message,
            parameters: [],
            root: $root,
            propertyPath: $propertyPath,
            invalidValue: $invalidValue
        );
    }

    /**
     * Helper to build a ConstraintViolationList
     */
    protected function createConstraintViolationList(array $violations = []): ConstraintViolationList
    {
        return new ConstraintViolationList($violations);
    }
}
