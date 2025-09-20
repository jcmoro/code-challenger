<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Entity\BookingRequest;
use App\Domain\Service\ProfitCalculator;
use App\Domain\ValueObject\ProfitStats;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ProfitCalculatorTest extends TestCase
{
    private ProfitCalculator $profitCalculator;

    protected function setUp(): void
    {
        $this->profitCalculator = new ProfitCalculator();
    }

    private function createBookingRequest(float $sellingRate, float $margin, int $nights): BookingRequest
    {
        return new BookingRequest(
            requestId: uniqid('req_', true),
            checkIn: new DateTimeImmutable('2025-01-01'),
            nights: $nights,
            sellingRate: $sellingRate,
            margin: $margin
        );
    }

    public function testCalculatesStatsForEmptyArray(): void
    {
        $result = $this->profitCalculator->calculateStats([]);

        $this->assertInstanceOf(ProfitStats::class, $result);
        $this->assertEqualsWithDelta(0.0, $result->avgNight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->minNight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $result->maxNight, PHP_FLOAT_EPSILON);
    }

    public function testCalculatesStatsForSingleBooking(): void
    {
        $requests = [$this->createBookingRequest(100, 20, 5)];

        $result = $this->profitCalculator->calculateStats($requests);

        $this->assertEqualsWithDelta(4.0, $result->avgNight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(4.0, $result->minNight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(4.0, $result->maxNight, PHP_FLOAT_EPSILON);
    }

    public function testCalculatesStatsForMultipleBookings(): void
    {
        $requests = [
            $this->createBookingRequest(200, 20, 4), // profit per night = 10
            $this->createBookingRequest(150, 30, 3), // profit per night = 15
        ];

        $result = $this->profitCalculator->calculateStats($requests);

        $this->assertEqualsWithDelta(12.5, $result->avgNight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(10.0, $result->minNight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(15.0, $result->maxNight, PHP_FLOAT_EPSILON);
    }

    public function testRoundsResultsToTwoDecimalPlaces(): void
    {
        $requests = [
            $this->createBookingRequest(123.45, 12.34, 7),
            $this->createBookingRequest(234.56, 23.45, 6),
        ];

        $result = $this->profitCalculator->calculateStats($requests);

        $this->assertSame(round($result->avgNight, 2), $result->avgNight);
        $this->assertSame(round($result->minNight, 2), $result->minNight);
        $this->assertSame(round($result->maxNight, 2), $result->maxNight);
    }
}
