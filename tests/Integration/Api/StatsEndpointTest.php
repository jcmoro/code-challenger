<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class StatsEndpointTest extends WebTestCase
{
    public function testStatsEndpointReturnsCorrectStats(): void
    {
        $client = self::createClient();

        $requestData = [
            [
                'request_id' => 'bookata_XY123',
                'check_in' => '2020-01-01',
                'nights' => 1,
                'selling_rate' => 50.0,
                'margin' => 20.0,
            ],
            [
                'request_id' => 'kayete_PP234',
                'check_in' => '2020-01-04',
                'nights' => 1,
                'selling_rate' => 55.0,
                'margin' => 22.0,
            ],
            [
                'request_id' => 'trivoltio_ZX69',
                'check_in' => '2020-01-07',
                'nights' => 1,
                'selling_rate' => 49.0,
                'margin' => 21.0,
            ],
        ];

        $client->request(
            Request::METHOD_POST,
            '/stats',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('avg_night', $responseData);
        $this->assertArrayHasKey('min_night', $responseData);
        $this->assertArrayHasKey('max_night', $responseData);

        $this->assertEqualsWithDelta(10.80, $responseData['avg_night'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(10.0, $responseData['min_night'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(12.1, $responseData['max_night'], PHP_FLOAT_EPSILON);
    }

    public function testStatsEndpointRejectsBadRequest(): void
    {
        $client = self::createClient();

        $client->request(
            Request::METHOD_POST,
            '/stats',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testStatsEndpointHandlesValidationErrors(): void
    {
        $client = self::createClient();

        $requestData = [
            [
                'request_id' => '', // Invalid: empty
                'check_in' => '2020-01-01',
                'nights' => 1,
                'selling_rate' => 50.0,
                'margin' => 20.0,
            ]
        ];

        $client->request(
            Request::METHOD_POST,
            '/stats',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
