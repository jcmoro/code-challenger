<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller\Api;

use Symfony\Component\HttpFoundation\Response;
use App\Application\DTO\BookingRequestDTO;
use App\Application\UseCase\CalculateStats\CalculateStatsUseCaseInterface;
use App\Domain\ValueObject\ProfitStats;
use App\Infrastructure\Controller\Api\StatsController;
use App\Infrastructure\Http\Request\BookingRequestParserInterface;
use App\Infrastructure\Http\Response\ErrorResponseInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class StatsControllerTest extends TestCase
{
    private CalculateStatsUseCaseInterface $useCase;

    private BookingRequestParserInterface $parser;

    private ErrorResponseInterface $errorResponse;

    private StatsController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = Mockery::mock(CalculateStatsUseCaseInterface::class);
        $this->parser = Mockery::mock(BookingRequestParserInterface::class);
        $this->errorResponse = Mockery::mock(ErrorResponseInterface::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        $this->controller = new StatsController(
            $this->useCase,
            $this->parser,
            $this->errorResponse,
            $logger
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testStatsReturnsSuccessfulResponse(): void
    {
        $dto = new BookingRequestDTO();
        $dto->requestId = 'req1';
        $dto->checkIn = '2025-01-01';
        $dto->nights = 2;
        $dto->sellingRate = 100;
        $dto->margin = 20;

        $profitStats = new ProfitStats(50.0, 40.0, 60.0);

        $this->parser
            ->shouldReceive('parse')
            ->once()
            ->andReturn([$dto]);

        $this->useCase
            ->shouldReceive('execute')
            ->once()
            ->andReturn($profitStats);

        $this->errorResponse
            ->shouldReceive('respond')
            ->andReturn(new JsonResponse(['error' => 'something'], Response::HTTP_BAD_REQUEST));

        $request = new Request([], [], [], [], [], [], json_encode([$dto]));
        $response = ($this->controller)($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
    }
}
