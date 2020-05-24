<?php

/*
 * This file is part of michaelbutler/phposh.
 * Source: https://github.com/michaelbutler/phposh
 *
 * (c) Michael Butler <michael@butlerpc.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file named LICENSE.
 */

namespace PHPoshTests\Unit\Provider\Poshmark;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPosh\Provider\Poshmark\PoshmarkService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPosh\Provider\Poshmark\PoshmarkService
 *
 * @internal
 */
class PoshmarkServiceTest extends TestCase
{
    public function providerForCookieStrings(): array
    {
        $cookieString = $this->getExampleCookies();

        return [
            [
                $cookieString, // cookie string
                [ // expected decoded array
                    '_csrf' => '123',
                    '__ssid' => 'abc',
                    'exp' => 'word space',
                    'ui' => '{"dh":"a","em":"b","uid":"c","fn":"John%20Smith"}',
                    '_uetsid' => 'foo_y',
                    '_derived_epik' => 'foo_z',
                    '_web_session' => 'aa',
                    'jwt' => 'bb',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForCookieStrings
     */
    public function testCookieDecoding(string $cookieStr, array $expectedCookies): void
    {
        $poshmark = new PoshmarkService($cookieStr);

        $method = new \ReflectionMethod(PoshmarkService::class, 'getCookies');
        $method->setAccessible(true);

        $this->assertSame($expectedCookies, $method->invoke($poshmark));
    }

    public function testGetItemInDataElement(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $body_data = file_get_contents(DATA_DIR . '/item_response_1.json');
        $mockClient = $this->getMockGuzzleClient([
            new Response(200, ['X-Test' => 'true'], $body_data),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $item = $service->getItem('abcdefg123456');

        // Assert Request was made as expected
        $firstRequest = array_pop($container);

        // Assert Response
        $this->assertSame('5de18684a6e3ea2a8a0ba67a', $item->getId());
        $this->assertSame('Arizona U Tigers pull over hoodie', $item->getTitle());
        $this->assertSame('Great condition, nice University sweatshirt with hood.', $item->getDescription());
    }

    public function testGetItemInvalidId(): void
    {
        $service = $this->getPoshmarkService();
        $this->expectException(\InvalidArgumentException::class);
        $service->getItem('');
    }

    public function testGetItems(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $body_data1 = file_get_contents(DATA_DIR . '/multi_item_response_1.json');
        $body_data2 = file_get_contents(DATA_DIR . '/multi_item_response_2.json');
        $mockClient = $this->getMockGuzzleClient([
            new Response(200, [], $body_data1),
            new Response(200, [], $body_data2),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $items = $service->getItems();

        foreach ($items as $item) {
            $this->assertRegExp('/^[a-f0-9]+$/i', $item->getId());
            $this->assertNotEmpty($item->getDescription());
            $this->assertGreaterThan(0.01, $item->getPrice()->getAmount());
        }

        $this->assertCount(2, $container);
    }

    public function testGetItemsOnEmptyCloset(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $body_data1 = [
            'data' => [],
            'more' => new \stdClass(),
            'req_id' => 'abc123f',
        ];
        $mockClient = $this->getMockGuzzleClient([
            new Response(200, [], json_encode($body_data1)),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $items = $service->getItems();
        $this->assertCount(0, $items);

        $this->assertCount(1, $container);
    }

    private function getMockGuzzleClient(array $responses, &$historyContainer)
    {
        // Create a mock and queue responses.
        $mock = new MockHandler($responses);
        $history = Middleware::history($historyContainer);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        return new Client(['handler' => $handlerStack]);
    }

    private function getPoshmarkService(): PoshmarkService
    {
        return new PoshmarkService($this->getExampleCookies());
    }

    private function getExampleCookies(): string
    {
        $userData = [
            'dh' => 'a',
            'em' => 'b',
            'uid' => 'c',
            'fn' => 'John%20Smith',
        ];

        $ui = json_encode($userData);
        $ui = rawurlencode($ui);

        // These are all the required cookie key=value pairs
        return "_csrf=123; __ssid=abc; exp=word space; ui={$ui}; _uetsid=foo_y; " .
            '_derived_epik=foo_z; _web_session=aa; jwt=bb;';
    }
}
