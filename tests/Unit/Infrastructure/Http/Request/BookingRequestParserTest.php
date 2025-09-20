<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Request;

use PHPUnit\Framework\MockObject\MockObject;
use App\Application\DTO\BookingRequestDTO;
use App\Infrastructure\Exception\ApiException;
use App\Infrastructure\Http\Request\BookingRequestParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class BookingRequestParserTest extends TestCase
{
    private MockObject $denormalizer;

    private MockObject $validator;

    private BookingRequestParser $parser;

    protected function setUp(): void
    {
        $this->denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->parser = new BookingRequestParser($this->denormalizer, $this->validator);
    }

    public function testParseValidDataReturnsDTOs(): void
    {
        $json = json_encode([
            ['request_id' => 'abc', 'check_in' => '2025-10-01', 'nights' => 2, 'selling_rate' => 100.0, 'margin' => 10.0]
        ]);

        $dto = new BookingRequestDTO();
        $dto->requestId = 'abc';
        $dto->checkIn = '2025-10-01';
        $dto->nights = 2;
        $dto->sellingRate = 100.0;
        $dto->margin = 10.0;

        $this->denormalizer
            ->method('denormalize')
            ->willReturn($dto);

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $result = $this->parser->parse($json);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(BookingRequestDTO::class, $result[0]);
        $this->assertSame('abc', $result[0]->requestId);
    }

    public function testParseInvalidJsonThrowsApiException(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $this->parser->parse('{invalid json}');
    }

    public function testParseValidationErrorsThrowsApiException(): void
    {
        $json = json_encode([
            ['request_id' => '', 'check_in' => '2025-10-01', 'nights' => 2, 'selling_rate' => 100.0, 'margin' => 10.0]
        ]);

        $dto = new BookingRequestDTO();

        $this->denormalizer
            ->method('denormalize')
            ->willReturn($dto);

        $violation = new ConstraintViolation('Request ID cannot be empty', '', [], '', 'requestId', '');
        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Item 0: Request ID cannot be empty');

        $this->parser->parse($json);
    }
}
