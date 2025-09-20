<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\MaximizeProfits;

use App\Application\DTO\BookingRequestDTO;
use App\Application\UseCase\MaximizeProfits\MaximizeProfitsUseCase;
use App\Domain\Service\BookingOptimizerInterface;
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

        $expectedResult = [
            'request_ids' => ['test_123'],
            'total_profit' => 40.0,
            'avg_night' => 8.0,
            'min_night' => 8.0,
            'max_night' => 8.0,
        ];

        $this->bookingOptimizer
            ->shouldReceive('findOptimalCombination')
            ->once()
            ->with(Mockery::on(function ($requests): bool {
                return count($requests) === 1
                    && $requests[0]->getRequestId() === 'test_123'
                    && $requests[0]->getNights() === 5;
            }))
            ->andReturn($expectedResult);

        $result = $this->useCase->execute([$dto]);

        $this->assertSame($expectedResult, $result);
    }
}
