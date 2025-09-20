<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final class BookingRequestDTO
{
    #[Assert\NotBlank(message: 'Request ID cannot be empty')]
    #[Assert\Type(type: 'string', message: 'Request ID must be a string')]
    #[SerializedName('request_id')]
    public string $requestId = '';

    #[Assert\NotBlank(message: 'Check-in date cannot be empty')]
    #[Assert\Date(message: 'Check-in date must be a valid date')]
    #[SerializedName('check_in')]
    public string $checkIn = '';

    #[Assert\NotBlank(message: 'Nights cannot be empty')]
    #[Assert\Type(type: 'integer', message: 'Nights must be an integer')]
    #[Assert\GreaterThan(value: 0, message: 'Nights must be greater than 0')]
    #[SerializedName('nights')]
    public int $nights = 0;

    #[Assert\NotBlank(message: 'Selling rate cannot be empty')]
    #[Assert\Type(type: 'numeric', message: 'Selling rate must be numeric')]
    #[Assert\GreaterThan(value: 0, message: 'Selling rate must be greater than 0')]
    #[SerializedName('selling_rate')]
    public float $sellingRate = 0.0;

    #[Assert\NotBlank(message: 'Margin cannot be empty')]
    #[Assert\Type(type: 'numeric', message: 'Margin must be numeric')]
    #[Assert\GreaterThan(value: 0, message: 'Margin must be greater than 0')]
    #[Assert\LessThanOrEqual(value: 100, message: 'Margin must be less than or equal to 100')]
    #[SerializedName('margin')]
    public float $margin = 0.0;
}
