<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Controller\Api;

use App\Application\DTO\BookingRequestDTO;
use App\Application\UseCase\MaximizeProfits\MaximizeProfitsUseCaseInterface;
use App\Infrastructure\Controller\Api\MaximizeController;
use App\Infrastructure\Exception\ApiException;
use App\Infrastructure\Http\Request\BookingRequestParserInterface;
use App\Infrastructure\Http\Response\ErrorResponseInterface;
use App\Tests\Unit\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mockery;

final class MaximizeControllerTest extends TestCase
{
    public $parser;

    public $errorResponse;

    private MaximizeProfitsUseCaseInterface $maximizeProfitsUseCase;

    private MaximizeController $controller;

    protected function setUp(): void
    {
        $this->maximizeProfitsUseCase = Mockery::mock(MaximizeProfitsUseCaseInterface::class);
        $this->parser = \Mockery::mock(BookingRequestParserInterface::class);
        $this->errorResponse = \Mockery::mock(ErrorResponseInterface::class);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        $this->controller = new MaximizeController(
            $this->maximizeProfitsUseCase,
            $this->parser,
            $this->errorResponse,
            $logger
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testMaximizeReturnsSuccessfulResponse(): void
    {
        $requestData = [
            [
                'request_id' => 'bookata_XY123',
                'check_in' => '2020-01-01',
                'nights' => 5,
                'selling_rate' => 200.0,
                'margin' => 20.0,
            ],
            [
                'request_id' => 'acme_AAAAA',
                'check_in' => '2020-01-10',
                'nights' => 4,
                'selling_rate' => 160.0,
                'margin' => 30.0,
            ]
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        // Create real DTOs instead of mocking
        $dto1 = new BookingRequestDTO();
        $dto1->requestId = 'bookata_XY123';
        $dto1->checkIn = '2020-01-01';
        $dto1->nights = 5;
        $dto1->sellingRate = 200.0;
        $dto1->margin = 20.0;

        $dto2 = new BookingRequestDTO();
        $dto2->requestId = 'acme_AAAAA';
        $dto2->checkIn = '2020-01-10';
        $dto2->nights = 4;
        $dto2->sellingRate = 160.0;
        $dto2->margin = 30.0;

        $this->parser
            ->shouldReceive('parse')
            ->once()
            ->with(json_encode($requestData))
            ->andReturn([$dto1, $dto2]);

        $expectedResult = [
            'request_ids' => ['bookata_XY123', 'acme_AAAAA'],
            'total_profit' => 88.0,
            'avg_night' => 10.0,
            'min_night' => 8.0,
            'max_night' => 12.0,
        ];

        $this->maximizeProfitsUseCase
            ->shouldReceive('execute')
            ->once()
            ->with([$dto1, $dto2])
            ->andReturn($expectedResult);

        $response = $this->controller->maximize($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
        $this->assertJsonStringEqualsJsonString(json_encode($expectedResult), $response->getContent());
    }

    public function testMaximizeReturnsBadRequestForInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], '{"invalid_json"}');

        $this->parser
            ->shouldReceive('parse')
            ->once()
            ->with($request->getContent())
            ->andThrow(ApiException::badRequest('Invalid data format'));

        $this->errorResponse
            ->shouldReceive('respond')
            ->once()
            ->with(\Mockery::type(\Throwable::class))
            ->andReturn(new JsonResponse(['error' => 'Invalid data format'], Response::HTTP_BAD_REQUEST));

        $response = $this->controller->maximize($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), (string) $response->getContent());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Invalid data format']),
            $response->getContent()
        );
    }


    public function testMaximizeHandlesValidationErrors(): void
    {
        $requestData = [
            [
                'request_id' => '',
                'check_in' => '2020-01-01',
                'nights' => -1,
                'selling_rate' => 200.0,
                'margin' => 20.0,
            ]
        ];

        $request = new Request([], [], [], [], [], [], json_encode($requestData));

        $this->parser
            ->shouldReceive('parse')
            ->once()
            ->with(json_encode($requestData))
            ->andThrow(ApiException::badRequest('Nights must be greater than 0'));

        $this->errorResponse
            ->shouldReceive('respond')
            ->once()
            ->with(\Mockery::type(ApiException::class))
            ->andReturn(new JsonResponse(['error' => 'Nights must be greater than 0'], Response::HTTP_BAD_REQUEST));

        $response = $this->controller->maximize($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), (string) $response->getContent());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Nights must be greater than 0']),
            $response->getContent()
        );
    }
}
