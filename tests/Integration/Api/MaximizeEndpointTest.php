<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class MaximizeEndpointTest extends WebTestCase
{
    public function testMaximizeEndpointReturnsOptimalCombination(): void
    {
        $client = self::createClient();

        $requestData = [
            [
                'request_id' => 'bookata_XY123',
                'check_in' => '2020-01-01',
                'nights' => 5,
                'selling_rate' => 200.0,
                'margin' => 20.0,
            ],
            [
                'request_id' => 'kayete_PP234',
                'check_in' => '2020-01-04',
                'nights' => 4,
                'selling_rate' => 156.0,
                'margin' => 5.0,
            ],
            [
                'request_id' => 'acme_AAAAA',
                'check_in' => '2020-01-10',
                'nights' => 4,
                'selling_rate' => 160.0,
                'margin' => 30.0,
            ],
        ];

        $client->request(
            Request::METHOD_POST,
            '/maximize',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('request_ids', $responseData);
        $this->assertArrayHasKey('total_profit', $responseData);
        $this->assertArrayHasKey('avg_night', $responseData);
        $this->assertArrayHasKey('min_night', $responseData);
        $this->assertArrayHasKey('max_night', $responseData);

        // Should select bookata_XY123 + acme_AAAAA for maximum profit
        $this->assertContains('bookata_XY123', $responseData['request_ids']);
        $this->assertContains('acme_AAAAA', $responseData['request_ids']);
        $this->assertEqualsWithDelta(88.0, $responseData['total_profit'], PHP_FLOAT_EPSILON);
    }

    public function testMaximizeEndpointHandlesNonOverlappingBookings(): void
    {
        $client = self::createClient();

        $requestData = [
            [
                'request_id' => 'booking_1',
                'check_in' => '2020-01-01',
                'nights' => 3,
                'selling_rate' => 100.0,
                'margin' => 10.0,
            ],
            [
                'request_id' => 'booking_2',
                'check_in' => '2020-01-05',
                'nights' => 2,
                'selling_rate' => 80.0,
                'margin' => 15.0,
            ],
        ];

        $client->request(
            Request::METHOD_POST,
            '/maximize',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Should select both since they don't overlap
        $this->assertCount(2, $responseData['request_ids']);
        $this->assertContains('booking_1', $responseData['request_ids']);
        $this->assertContains('booking_2', $responseData['request_ids']);
        $this->assertEqualsWithDelta(22.0, $responseData['total_profit'], PHP_FLOAT_EPSILON);
    }
}
