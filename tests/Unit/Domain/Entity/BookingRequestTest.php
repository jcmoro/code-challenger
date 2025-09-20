<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\BookingRequest;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BookingRequestTest extends TestCase
{
    public function testCanCreateBookingRequest(): void
    {
        $checkIn = new DateTimeImmutable('2020-01-01');
        $booking = new BookingRequest(
            requestId: 'test_123',
            checkIn: $checkIn,
            nights: 5,
            sellingRate: 200.0,
            margin: 20.0
        );

        $this->assertSame('test_123', $booking->getRequestId());
        $this->assertEquals($checkIn, $booking->getCheckIn());
        $this->assertSame(5, $booking->getNights());
        $this->assertEqualsWithDelta(200.0, $booking->getSellingRate(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(20.0, $booking->getMargin(), PHP_FLOAT_EPSILON);
    }

    public function testCalculatesProfitPerNightCorrectly(): void
    {
        $booking = new BookingRequest(
            requestId: 'test_123',
            checkIn: new DateTimeImmutable('2020-01-01'),
            nights: 5,
            sellingRate: 200.0,
            margin: 20.0
        );

        // (200 * 20 / 100) / 5 = 40 / 5 = 8.0
        $this->assertEqualsWithDelta(8.0, $booking->getProfitPerNight(), PHP_FLOAT_EPSILON);
    }

    public function testCalculatesTotalProfitCorrectly(): void
    {
        $booking = new BookingRequest(
            requestId: 'test_123',
            checkIn: new DateTimeImmutable('2020-01-01'),
            nights: 5,
            sellingRate: 200.0,
            margin: 20.0
        );

        // 200 * 20 / 100 = 40.0
        $this->assertEqualsWithDelta(40.0, $booking->getTotalProfit(), PHP_FLOAT_EPSILON);
    }

    public function testCalculatesCheckOutDateCorrectly(): void
    {
        $checkIn = new DateTimeImmutable('2020-01-01');
        $booking = new BookingRequest(
            requestId: 'test_123',
            checkIn: $checkIn,
            nights: 5,
            sellingRate: 200.0,
            margin: 20.0
        );

        $expectedCheckOut = new DateTimeImmutable('2020-01-06');
        $this->assertEquals($expectedCheckOut, $booking->getCheckOut());
    }

    public function testDetectsOverlappingBookings(): void
    {
        $booking1 = new BookingRequest(
            requestId: 'booking_1',
            checkIn: new DateTimeImmutable('2020-01-01'),
            nights: 5, // check-out: 2020-01-06
            sellingRate: 200.0,
            margin: 20.0
        );

        $booking2 = new BookingRequest(
            requestId: 'booking_2',
            checkIn: new DateTimeImmutable('2020-01-03'),
            nights: 5, // check-out: 2020-01-08
            sellingRate: 150.0,
            margin: 15.0
        );

        $this->assertTrue($booking1->overlapsWith($booking2));
        $this->assertTrue($booking2->overlapsWith($booking1));
    }

    public function testDetectsNonOverlappingBookings(): void
    {
        $booking1 = new BookingRequest(
            requestId: 'booking_1',
            checkIn: new DateTimeImmutable('2020-01-01'),
            nights: 5, // check-out: 2020-01-06
            sellingRate: 200.0,
            margin: 20.0
        );

        $booking2 = new BookingRequest(
            requestId: 'booking_2',
            checkIn: new DateTimeImmutable('2020-01-06'),
            nights: 5, // check-out: 2020-01-11
            sellingRate: 150.0,
            margin: 15.0
        );

        $this->assertFalse($booking1->overlapsWith($booking2));
        $this->assertFalse($booking2->overlapsWith($booking1));
    }

    public function testDetectsAdjacentBookings(): void
    {
        $booking1 = new BookingRequest(
            requestId: 'booking_1',
            checkIn: new DateTimeImmutable('2020-01-01'),
            nights: 5, // check-out: 2020-01-06
            sellingRate: 200.0,
            margin: 20.0
        );

        $booking2 = new BookingRequest(
            requestId: 'booking_2',
            checkIn: new DateTimeImmutable('2020-01-06'),
            nights: 3, // check-out: 2020-01-09
            sellingRate: 150.0,
            margin: 15.0
        );

        // Adjacent bookings should not overlap
        $this->assertFalse($booking1->overlapsWith($booking2));
        $this->assertFalse($booking2->overlapsWith($booking1));
    }
}
