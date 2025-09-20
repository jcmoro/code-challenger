<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Entity\BookingRequest;
use App\Domain\Service\BookingOptimizer;
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
            'total_profit' => 0,
            'avg_night' => 0,
            'min_night' => 0,
            'max_night' => 0,
        ];

        $this->assertSame($expected, $result);
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

        $this->assertSame(['single_booking'], $result['request_ids']);
        $this->assertEqualsWithDelta(40.0, $result['total_profit'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $result['avg_night'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $result['min_night'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $result['max_night'], PHP_FLOAT_EPSILON);
    }

    public function testSelectsOptimalCombinationWithOverlappingBookings(): void
    {
        $bookings = [
            // bookata_XY123: 2020-01-01 to 2020-01-06 (5 nights)
            // Profit: 200 * 0.20 = 40.0, Per night: 40/5 = 8.0
            new BookingRequest(
                requestId: 'bookata_XY123',
                checkIn: new DateTimeImmutable('2020-01-01'),
                nights: 5,
                sellingRate: 200.0,
                margin: 20.0
            ),

            // kayete_PP234: 2020-01-04 to 2020-01-08 (4 nights) - OVERLAPS with bookata_XY123
            // Profit: 156 * 0.05 = 7.8, Per night: 7.8/4 = 1.95
            new BookingRequest(
                requestId: 'kayete_PP234',
                checkIn: new DateTimeImmutable('2020-01-04'),
                nights: 4,
                sellingRate: 156.0,
                margin: 5.0
            ),

            // acme_AAAAA: 2020-01-10 to 2020-01-14 (4 nights) - NO OVERLAP
            // Profit: 160 * 0.30 = 48.0, Per night: 48/4 = 12.0
            new BookingRequest(
                requestId: 'acme_AAAAA',
                checkIn: new DateTimeImmutable('2020-01-10'),
                nights: 4,
                sellingRate: 160.0,
                margin: 30.0
            ),
        ];

        $result = $this->optimizer->findOptimalCombination($bookings);

        // Debug information for troubleshooting
        echo "\n=== DEBUG INFO ===\n";
        echo "Selected requests: " . json_encode($result['request_ids']) . "\n";
        echo "Total profit: " . $result['total_profit'] . "\n";

        // Combinations analysis:
        // - bookata_XY123 alone: 40.0
        // - kayete_PP234 alone: 7.8
        // - acme_AAAAA alone: 48.0
        // - bookata_XY123 + acme_AAAAA: 40.0 + 48.0 = 88.0 (OPTIMAL)
        // - kayete_PP234 + acme_AAAAA: 7.8 + 48.0 = 55.8
        // - kayete_PP234 conflicts with bookata_XY123

        $this->assertContains('bookata_XY123', $result['request_ids']);
        $this->assertContains('acme_AAAAA', $result['request_ids']);
        $this->assertNotContains('kayete_PP234', $result['request_ids']);
        $this->assertEqualsWithDelta(88.0, $result['total_profit'], PHP_FLOAT_EPSILON);
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

        $this->assertCount(3, $result['request_ids']);
        $this->assertContains('booking_1', $result['request_ids']);
        $this->assertContains('booking_2', $result['request_ids']);
        $this->assertContains('booking_3', $result['request_ids']);
        $this->assertEqualsWithDelta(45.0, $result['total_profit'], PHP_FLOAT_EPSILON);
    }

    public function testHandlesComplexOverlappingScenario(): void
    {
        // Scenario from the guidelines
        $bookings = [
            new BookingRequest(
                requestId: 'A',
                checkIn: new DateTimeImmutable('2018-01-01'),
                nights: 10, // check-out: 2018-01-11
                sellingRate: 1000.0,
                margin: 10.0 // total profit: 100.0
            ),
            new BookingRequest(
                requestId: 'B',
                checkIn: new DateTimeImmutable('2018-01-06'), // overlaps with A
                nights: 10, // check-out: 2018-01-16
                sellingRate: 700.0,
                margin: 10.0 // total profit: 70.0
            ),
            new BookingRequest(
                requestId: 'C',
                checkIn: new DateTimeImmutable('2018-01-12'), // overlaps with B, not with A
                nights: 10, // check-out: 2018-01-22
                sellingRate: 400.0,
                margin: 10.0 // total profit: 40.0
            ),
        ];

        $result = $this->optimizer->findOptimalCombination($bookings);

        // Should select A + C = 140.0 (better than just A = 100.0)
        $this->assertContains('A', $result['request_ids']);
        $this->assertContains('C', $result['request_ids']);
        $this->assertNotContains('B', $result['request_ids']);
        $this->assertEqualsWithDelta(140.0, $result['total_profit'], PHP_FLOAT_EPSILON);
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
        $this->assertTrue($booking1->overlapsWith($booking2), 'booking1 should overlap with booking2');
        $this->assertFalse($booking1->overlapsWith($booking3), 'booking1 should NOT overlap with booking3');
        $this->assertFalse($booking2->overlapsWith($booking3), 'booking2 should NOT overlap with booking3');

        // Verify profits
        $this->assertEqualsWithDelta(40.0, $booking1->getTotalProfit(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(7.8, $booking2->getTotalProfit(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(48.0, $booking3->getTotalProfit(), PHP_FLOAT_EPSILON);
    }
}
