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

    public function testGetItem(): void
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

    public function testGetOrderDetails(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $body_data = file_get_contents(DATA_DIR . '/order_details_1.html');
        $item_data1 = file_get_contents(DATA_DIR . '/item_response_1.json');
        $mockClient = $this->getMockGuzzleClient([
            new Response(200, ['Content-Type' => 'text/html'], $body_data),
            new Response(200, ['Content-Type' => 'application/json'], $item_data1),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $order = $service->getOrderDetails('abcdefg123456');

        // Note: we actually set the order id from the input, not from the response body
        $this->assertSame('abcdefg123456', $order->getId());
        $this->assertSame(1, $order->getItemCount());
        $this->assertSame('coolbuyer123', $order->getBuyerUsername());
        $this->assertSame('Arizona U Tigers pull over hoodie', $order->getTitle());
        $this->assertSame('$28.00', (string) $order->getOrderTotal());
        $this->assertSame('$26.30', (string) $order->getEarnings());
        $this->assertSame('$4.70', (string) $order->getPoshmarkFee());
        $this->assertSame('$3.24', (string) $order->getTaxes());
        $this->assertSame('2020-05-24', $order->getOrderDate()->format('Y-m-d'));
        // Order details don't have access to the size, but the individual items do
        $this->assertSame('', $order->getSize());

        $item = $order->getItems()[0];
        $this->assertSame('M', $item->getSize());
        $this->assertSame('5de18684a6e3ea2a8a0ba67a', $item->getId());
    }

    public function testGetOrderSummaries(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $body_data1 = file_get_contents(DATA_DIR . '/order_summaries_html_1.json');
        $body_data2 = file_get_contents(DATA_DIR . '/order_summaries_html_2.json');
        $mockClient = $this->getMockGuzzleClient([
            new Response(200, ['Content-Type' => 'application/json'], $body_data1),
            new Response(200, ['Content-Type' => 'application/json'], $body_data2),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $orders = $service->getOrderSummaries('20');
        $first_order = $orders[0];
        $second_order = $orders[1];

        // Assert first order summary
        $this->assertSame(
            '6adf3c045971a75920f59970',
            $first_order->getId()
        );
        $this->assertSame(
            'Nike XL Running Shorts Blue 857785 Flex 2in1 7" Sh',
            $first_order->getTitle()
        );
        $this->assertRegExp('/^Shopper/', $first_order->getBuyerUsername());

        // Assert second order summary
        $this->assertSame(
            '6f6a24eb1eb3c672f0f4faed',
            $second_order->getId()
        );
        $this->assertSame(
            'Tommy Bahama XL Polo Shirt Blue T20442 Mens Size P',
            $second_order->getTitle()
        );
        $this->assertRegExp('/^Shopper/', $second_order->getBuyerUsername());
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
