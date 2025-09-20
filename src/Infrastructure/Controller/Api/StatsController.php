<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api;

use App\Application\UseCase\CalculateStats\CalculateStatsUseCaseInterface;
use App\Domain\Exception\InvalidBookingRequestException;
use App\Infrastructure\Http\Request\BookingRequestParserInterface;
use App\Infrastructure\Http\Response\ErrorResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stats', name: 'api_stats', methods: ['POST'])]
final class StatsController extends AbstractController
{
    public function __construct(
        private readonly CalculateStatsUseCaseInterface $calculateStatsUseCase,
        private readonly BookingRequestParserInterface $parser,
        private readonly ErrorResponseInterface $errorResponse,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $requestId = uniqid('stats_', true);
        $this->logger->info('Stats endpoint called', ['request_id' => $requestId]);

        try {
            $dtos = $this->parser->parse($request->getContent());

            $result = $this->calculateStatsUseCase->execute($dtos);

            $this->logger->info('Stats calculated successfully', [
                'request_id' => $requestId,
                'result' => $result->toArray()
            ]);

            return new JsonResponse($result->toArray());
        } catch (InvalidBookingRequestException $e) {
            $this->logger->warning('Domain validation error', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse->respond($e);
        } catch (\Throwable $throwable) {
            $this->logger->error('Unexpected error in stats endpoint', [
                'request_id' => $requestId,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return $this->errorResponse->respond($throwable);
        }
    }
}
