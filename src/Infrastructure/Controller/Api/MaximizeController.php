<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api;

use App\Application\UseCase\MaximizeProfits\MaximizeProfitsUseCaseInterface;
use App\Infrastructure\Http\Request\BookingRequestParserInterface;
use App\Infrastructure\Http\Response\ErrorResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class MaximizeController extends AbstractController
{
    public function __construct(
        private readonly MaximizeProfitsUseCaseInterface $useCase,
        private readonly BookingRequestParserInterface $parser,
        private readonly ErrorResponseInterface $errorResponse,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/maximize', name: 'api_maximize', methods: ['POST'])]
    public function maximize(Request $request): JsonResponse
    {
        $requestId = uniqid('maximize_', true);
        $this->logger->info('Maximize endpoint called', ['request_id' => $requestId]);

        try {
            $dtos = $this->parser->parse($request->getContent());

            $result = $this->useCase->execute($dtos);

            $this->logger->info('Optimization completed', [
                'request_id' => $requestId,
                'selected_requests' => $result->getRequestIds(),
                'total_profit' => $result->getTotalProfit(),
            ]);

            return new JsonResponse($result->toArray());
        } catch (\Throwable $throwable) {
            $this->logger->error('Error in maximize endpoint', [
                'request_id' => $requestId,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return $this->errorResponse->respond($throwable);
        }
    }
}
