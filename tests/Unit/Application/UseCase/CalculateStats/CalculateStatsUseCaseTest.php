<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\CalculateStats;

use App\Application\DTO\BookingRequestDTO;
use App\Application\UseCase\CalculateStats\CalculateStatsUseCase;
use App\Domain\Service\ProfitCalculatorInterface;
use App\Domain\ValueObject\ProfitStats;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CalculateStatsUseCaseTest extends TestCase
{
    private ProfitCalculatorInterface $profitCalculator;

    private CalculateStatsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profitCalculator = Mockery::mock(ProfitCalculatorInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        $this->useCase = new CalculateStatsUseCase(
            $this->profitCalculator,
            $logger
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testExecuteCallsProfitCalculatorWithCorrectData(): void
    {
        $dto = new BookingRequestDTO();
        $dto->requestId = 'req1';
        $dto->checkIn = '2025-01-01';
        $dto->nights = 2;
        $dto->sellingRate = 100;
        $dto->margin = 20;

        $profitStats = new ProfitStats(50.0, 40.0, 60.0);

        $this->profitCalculator
            ->shouldReceive('calculateStats')
            ->once()
            ->andReturn($profitStats);

        $result = $this->useCase->execute([$dto]);

        $this->assertInstanceOf(ProfitStats::class, $result);
        $this->assertEqualsWithDelta(50.0, $result->avgNight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(40.0, $result->minNight, PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(60.0, $result->maxNight, PHP_FLOAT_EPSILON);
    }
}
