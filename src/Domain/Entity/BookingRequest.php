<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\InvalidBookingRequestException;
use DateTimeImmutable;

final readonly class BookingRequest
{
    public function __construct(
        private string $requestId,
        private DateTimeImmutable $checkIn,
        private int $nights,
        private float $sellingRate,
        private float $margin
    ) {
        $this->validateRequestId($requestId);
        $this->validateNights($nights);
        $this->validateSellingRate($sellingRate);
        $this->validateMargin($margin);
    }

    public function getProfitPerNight(): float
    {
        return ($this->sellingRate * $this->margin / 100) / $this->nights;
    }

    public function getTotalProfit(): float
    {
        return $this->sellingRate * $this->margin / 100;
    }

    public function getCheckOut(): DateTimeImmutable
    {
        return $this->checkIn->modify(sprintf('+%d days', $this->nights));
    }

    public function overlapsWith(self $other): bool
    {
        return $this->checkIn < $other->getCheckOut() && $other->getCheckIn() < $this->getCheckOut();
    }

    private function validateRequestId(string $requestId): void
    {
        if (trim($requestId) === '') {
            throw InvalidBookingRequestException::emptyRequestId();
        }
    }

    private function validateNights(int $nights): void
    {
        if ($nights <= 0) {
            throw InvalidBookingRequestException::invalidNights($nights);
        }
    }

    private function validateSellingRate(float $sellingRate): void
    {
        if ($sellingRate <= 0) {
            throw InvalidBookingRequestException::invalidSellingRate($sellingRate);
        }
    }

    private function validateMargin(float $margin): void
    {
        if ($margin <= 0 || $margin > 100) {
            throw InvalidBookingRequestException::invalidMargin($margin);
        }
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getCheckIn(): DateTimeImmutable
    {
        return $this->checkIn;
    }

    public function getNights(): int
    {
        return $this->nights;
    }

    public function getSellingRate(): float
    {
        return $this->sellingRate;
    }

    public function getMargin(): float
    {
        return $this->margin;
    }
}
