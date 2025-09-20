<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

final class InvalidBookingRequestException extends DomainException
{
    public static function emptyRequestId(): self
    {
        return new self('Request ID cannot be empty');
    }

    public static function invalidNights(int $nights): self
    {
        return new self(sprintf('Nights must be greater than 0, got: %d', $nights));
    }

    public static function invalidSellingRate(float $sellingRate): self
    {
        return new self(sprintf('Selling rate must be greater than 0, got: %.2f', $sellingRate));
    }

    public static function invalidMargin(float $margin): self
    {
        return new self(sprintf('Margin must be between 0 and 100, got: %.2f', $margin));
    }

    public static function invalidCheckInDate(string $checkIn): self
    {
        return new self(sprintf('Invalid check-in date format: %s', $checkIn));
    }
}
