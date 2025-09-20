<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Entity\BookingRequest;
use App\Domain\Service\BookingOptimizer;
use App\Domain\ValueObject\BookingOptimizationResult;
use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BookingOptimizerTest extends TestCase
{
    private BookingOptimizer $optimizer;

    protected function setUp(): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        $this->optimizer = new BookingOptimizer($logger);
    }

    /**
     * @throws \Throwable
     */
    public function testReturnsEmptyResultForEmptyInput(): void
    {
        $result = $this->optimizer->findOptimalCombination([]);

        $expected = [
            'request_ids' => [],
            'total_profit' => 0.0,
            'avg_night' => 0.0,
            'min_night' => 0.0,
            'max_night' => 0.0,
        ];

        $this->assertInstanceOf(BookingOptimizationResult::class, $result);
        $this->assertSame($expected, $result->toArray());
    }

    /**
     * @throws \Throwable
     */
    public function testReturnsSingleBookingWhenOnlyOne(): void
    {
        $booking = new BookingRequest(
            requestId: 'single_booking',
            checkIn: new DateTimeImmutable('2020-01-01'),
            nights: 5,
            sellingRate: 200.0,
            margin: 20.0
        );

        $result = $this->optimizer->findOptimalCombination([$booking]);

        $this->assertInstanceOf(BookingOptimizationResult::class, $result);
        $this->assertSame(['single_booking'], $result->getRequestIds());
        $this->assertEqualsWithDelta(40.0, $result->getTotalProfit(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $result->getAvgNight(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $result->getMinNight(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $result->getMaxNight(), PHP_FLOAT_EPSILON);
    }

    public function testSelectsOptimalCombinationWithOverlappingBookings(): void
    {
        $bookings = [
            new BookingRequest(
                requestId: 'bookata_XY123',
                checkIn: new DateTimeImmutable('2020-01-01'),
                nights: 5,
                sellingRate: 200.0,
                margin: 20.0
            ),
            new BookingRequest(
                requestId: 'kayete_PP234',
                checkIn: new DateTimeImmutable('2020-01-04'),
                nights: 4,
                sellingRate: 156.0,
                margin: 5.0
            ),
            new BookingRequest(
                requestId: 'acme_AAAAA',
                checkIn: new DateTimeImmutable('2020-01-10'),
                nights: 4,
                sellingRate: 160.0,
                margin: 30.0
            ),
        ];

        $result = $this->optimizer->findOptimalCombination($bookings);

        $this->assertInstanceOf(BookingOptimizationResult::class, $result);
        $this->assertContains('bookata_XY123', $result->getRequestIds());
        $this->assertContains('acme_AAAAA', $result->getRequestIds());
        $this->assertNotContains('kayete_PP234', $result->getRequestIds());
        $this->assertEqualsWithDelta(88.0, $result->getTotalProfit(), PHP_FLOAT_EPSILON);
    }

    public function testSelectsAllNonOverlappingBookings(): void
    {
        $bookings = [
            new BookingRequest(
                requestId: 'booking_1',
                checkIn: new DateTimeImmutable('2020-01-01'),
                nights: 3, // check-out: 2020-01-04
                sellingRate: 100.0,
                margin: 10.0 // total profit: 10.0
            ),
            new BookingRequest(
                requestId: 'booking_2',
                checkIn: new DateTimeImmutable('2020-01-05'),
                nights: 3, // check-out: 2020-01-08
                sellingRate: 150.0,
                margin: 10.0 // total profit: 15.0
            ),
            new BookingRequest(
                requestId: 'booking_3',
                checkIn: new DateTimeImmutable('2020-01-10'),
                nights: 2, // check-out: 2020-01-12
                sellingRate: 80.0,
                margin: 25.0 // total profit: 20.0
            ),
        ];

        $result = $this->optimizer->findOptimalCombination($bookings);

        $this->assertInstanceOf(BookingOptimizationResult::class, $result);
        $this->assertCount(3, $result->getRequestIds());
        $this->assertContains('booking_1', $result->getRequestIds());
        $this->assertContains('booking_2', $result->getRequestIds());
        $this->assertContains('booking_3', $result->getRequestIds());
        $this->assertEqualsWithDelta(45.0, $result->getTotalProfit(), PHP_FLOAT_EPSILON);
    }

    public function testHandlesComplexOverlappingScenario(): void
    {
        $bookings = [
            new BookingRequest(
                requestId: 'A',
                checkIn: new DateTimeImmutable('2018-01-01'),
                nights: 10, // check-out: 2018-01-11
                sellingRate: 1000.0,
                margin: 10.0 // total beneficio: 100.0
            ),
            new BookingRequest(
                requestId: 'B',
                checkIn: new DateTimeImmutable('2018-01-06'), // se solapa con A
                nights: 10, // check-out: 2018-01-16
                sellingRate: 700.0,
                margin: 10.0 // total beneficio: 70.0
            ),
            new BookingRequest(
                requestId: 'C',
                checkIn: new DateTimeImmutable('2018-01-12'), // se solapa con B, pero no con A
                nights: 10, // check-out: 2018-01-22
                sellingRate: 400.0,
                margin: 10.0 // total beneficio: 40.0
            ),
        ];

        $result = $this->optimizer->findOptimalCombination($bookings);

        $this->assertInstanceOf(BookingOptimizationResult::class, $result);
        $this->assertContains('A', $result->getRequestIds());
        $this->assertContains('C', $result->getRequestIds());
        $this->assertNotContains('B', $result->getRequestIds());
        $this->assertEqualsWithDelta(140.0, $result->getTotalProfit(), PHP_FLOAT_EPSILON);
    }

    public function testSpecificOverlapScenarioFromOptimizer(): void
    {
        $booking1 = new BookingRequest(
            requestId: 'bookata_XY123',
            checkIn: new DateTimeImmutable('2020-01-01'),
            nights: 5, // check-out: 2020-01-06
            sellingRate: 200.0,
            margin: 20.0
        );

        $booking2 = new BookingRequest(
            requestId: 'kayete_PP234',
            checkIn: new DateTimeImmutable('2020-01-04'),
            nights: 4, // check-out: 2020-01-08
            sellingRate: 156.0,
            margin: 5.0
        );

        $booking3 = new BookingRequest(
            requestId: 'acme_AAAAA',
            checkIn: new DateTimeImmutable('2020-01-10'),
            nights: 4, // check-out: 2020-01-14
            sellingRate: 160.0,
            margin: 30.0
        );

        // Verify overlaps
        $this->assertTrue($booking1->overlapsWith($booking2), 'booking1 solapa con booking2');
        $this->assertFalse($booking1->overlapsWith($booking3), 'booking1 no solapa con booking3');
        $this->assertFalse($booking2->overlapsWith($booking3), 'booking2 no solapa con booking3');

        // Verify profits
        $this->assertEqualsWithDelta(40.0, $booking1->getTotalProfit(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(7.8, $booking2->getTotalProfit(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(48.0, $booking3->getTotalProfit(), PHP_FLOAT_EPSILON);
    }
}
