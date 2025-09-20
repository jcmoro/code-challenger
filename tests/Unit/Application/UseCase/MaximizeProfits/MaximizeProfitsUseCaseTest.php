<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\MaximizeProfits;

use App\Application\DTO\BookingRequestDTO;
use App\Application\UseCase\MaximizeProfits\MaximizeProfitsUseCase;
use App\Domain\Service\BookingOptimizerInterface;
use App\Domain\ValueObject\BookingOptimizationResult;
use PHPUnit\Framework\TestCase;
use Mockery;

final class MaximizeProfitsUseCaseTest extends TestCase
{
    private BookingOptimizerInterface $bookingOptimizer;

    private MaximizeProfitsUseCase $useCase;

    protected function setUp(): void
    {
        $this->bookingOptimizer = Mockery::mock(BookingOptimizerInterface::class);
        $this->useCase = new MaximizeProfitsUseCase($this->bookingOptimizer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testExecuteCallsBookingOptimizerWithCorrectData(): void
    {
        $dto = new BookingRequestDTO();
        $dto->requestId = 'test_123';
        $dto->checkIn = '2020-01-01';
        $dto->nights = 5;
        $dto->sellingRate = 200.0;
        $dto->margin = 20.0;

        $expectedVo = new BookingOptimizationResult(
            requestIds: ['test_123'],
            totalProfit: 40.0,
            avgNight: 8.0,
            minNight: 8.0,
            maxNight: 8.0
        );

        $this->bookingOptimizer
            ->shouldReceive('findOptimalCombination')
            ->once()
            ->with(Mockery::on(function ($requests): bool {
                return count($requests) === 1
                    && $requests[0]->getRequestId() === 'test_123'
                    && $requests[0]->getNights() === 5;
            }))
            ->andReturn($expectedVo);

        $result = $this->useCase->execute([$dto]);

        $this->assertInstanceOf(BookingOptimizationResult::class, $result);
        $this->assertSame(['test_123'], $result->getRequestIds());
        $this->assertEqualsWithDelta(40.0, $result->getTotalProfit(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $result->getAvgNight(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $result->getMinNight(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(8.0, $result->getMaxNight(), PHP_FLOAT_EPSILON);
    }
}
