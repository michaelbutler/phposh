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
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPosh\Exception\DataException;
use PHPosh\Exception\ItemNotFoundException;
use PHPosh\Exception\OrderNotFoundException;
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
        $cookieStringWithDoubleQuotes = '"' . $cookieString . '"';
        $cookieStringWithSingleQuotes = "'" . $cookieString . "'";

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
            [
                $cookieStringWithDoubleQuotes, // cookie string
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
            [
                $cookieStringWithSingleQuotes, // cookie string
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

    public function testGetItemWhen404NotFound(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        // Note: this is not actually representative exactly of what we'd get
        $body_data = '{"error": "404 Not found!"}';
        $mockClient = $this->getMockGuzzleClient([
            new Response(404, ['X-Test' => 'true', 'Content-Type' => 'application/json'], $body_data),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $this->expectException(ItemNotFoundException::class);
        $this->expectExceptionMessageRegExp('/Item .* not found/');
        $service->getItem('abcdefg123456');
    }

    public function testGetItemWhen500ServerError(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        // Note: this is not actually representative exactly of what we'd get
        $body_data = '{"error": "500 Not found!"}';
        $mockClient = $this->getMockGuzzleClient([
            new Response(500, ['X-Test' => 'true', 'Content-Type' => 'application/json'], $body_data),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $this->expectException(DataException::class);
        $this->expectExceptionMessageRegExp('/500 Internal Server Error/');
        $service->getItem('abcdefg123456');
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

    public function testGetItemsWhenDataExceptionOccursInitially(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $mockClient = $this->getMockGuzzleClient([
            new ServerException(
                'Server 500 Failure',
                new Request('get', PoshmarkService::BASE_URL . '/items')
            ),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $this->expectException(DataException::class);
        $service->getItems();
    }

    /**
     * When paginating items, if we get _some_ data back, but while paginating a later page fails, we should just
     * return what we got successfully.
     */
    public function testGetItemsWhenSecondPaginationThrowsException(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $body_data1 = file_get_contents(DATA_DIR . '/multi_item_response_1.json');
        $mockClient = $this->getMockGuzzleClient([
            new Response(200, [], $body_data1),
            new ServerException(
                'Server 500 Failure',
                new Request('get', PoshmarkService::BASE_URL . '/items')
            ),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $items = $service->getItems();
        $this->assertCount(20, $items);
        $firstItem = $items[0];
        // Note: Items are sorted by ID on return.
        $this->assertSame('Vera Bradley book bag', $firstItem->getTitle());
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

    public function testGetOrderDetailsWithInvalidId(): void
    {
        $service = $this->getPoshmarkService();
        $this->expectException(\InvalidArgumentException::class);
        $service->getOrderDetails('');
    }

    public function testGetOrderDetailsOnServerException(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $mockClient = $this->getMockGuzzleClient([
            new ClientException(
                404,
                new Request('get', PoshmarkService::BASE_URL . '/order/abc123')
            ),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $this->expectException(OrderNotFoundException::class);
        $service->getOrderDetails('abcdefg123456');
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

    public function testGetOrderDetailsWhenItemLookupsFail(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $body_data = file_get_contents(DATA_DIR . '/order_details_1.html');
        $mockClient = $this->getMockGuzzleClient([
            new Response(200, ['Content-Type' => 'text/html'], $body_data),
            new ClientException(
                'Item not found',
                new Request('get', PoshmarkService::BASE_URL . '/posts/abc123'),
                new Response(404)
            ),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $order = $service->getOrderDetails('abcdefg123456');

        // Note: we actually set the order id from the input, not from the response body
        $this->assertSame('abcdefg123456', $order->getId());
    }

    public function providerForInvalidLimits(): array
    {
        return [
            [0],
            [23000],
        ];
    }

    /**
     * @dataProvider providerForInvalidLimits
     *
     * @throws DataException
     */
    public function testGetOrderSummariesWithInvalidArgument(int $inputLimit): void
    {
        $service = $this->getPoshmarkService();
        $this->expectException(\InvalidArgumentException::class);
        $service->getOrderSummaries($inputLimit);
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

    public function testUpdateItemRequestWithInvalidIdInput(): void
    {
        $service = $this->getPoshmarkService();
        $this->expectException(\InvalidArgumentException::class);
        $service->updateItemRequest('', []);
    }

    public function testUpdateItemRequestWhenItemIsNotFound(): void
    {
        $service = $this->getPoshmarkService();

        $container = [];

        // Note: not representative of reality
        $bodyData = '{"error": "404 Not Found"}';

        $mockClient = $this->getMockGuzzleClient([
            new Response(404, ['Content-Type' => 'application/json'], $bodyData),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $this->expectException(ItemNotFoundException::class);
        $service->updateItemRequest('abc123def456789', []);
    }

    public function testUpdateItemRequestWhenFinalPostFails(): void
    {
        $newFields = [
            'title' => 'Cool title!!! 7',
            'price' => '$134.00',
        ];
        $service = $this->getPoshmarkService();

        $container = [];
        $itemResponse = file_get_contents(DATA_DIR . '/item_response_1.json');
        $xsrfResponse = <<<'HTML'
<html>
<head>
<meta name="x_csrf_token" id="csrftoken" content="XYZ_TOKEN_ABC" />
</head>
<body>
<p>blah blah blah</p>
<div id=""></div>
</body></html>
HTML;

        $mockClient = $this->getMockGuzzleClient([
            new Response(200, ['Content-Type' => 'application/json'], $itemResponse),
            new Response(200, ['Content-Type' => 'text/html'], $xsrfResponse),
            new Response(403, ['Content-Type' => 'application/json'], '{"error": "Logged out"}'),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $this->expectException(DataException::class);
        $this->expectExceptionMessageRegExp('/Logged out/');
        $this->expectExceptionCode(403);
        $service->updateItemRequest('abc123def456789', $newFields);
    }

    public function testGetItemWhenInvalidJSONIsReturned(): void
    {
        $service = $this->getPoshmarkService();
        $container = [];
        $body_data = '=00.932}{{;;';
        $mockClient = $this->getMockGuzzleClient([
            new Response(200, ['X-Test' => 'true', 'Content-Type' => 'application/json'], $body_data),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $this->expectException(DataException::class);
        $this->expectExceptionCode(500);
        $service->getItem('abcdefg123456');
    }

    public function testUpdateItemRequest(): void
    {
        $newFields = [
            'title' => 'Cool title!!! 7',
            'price' => '$134.00',
        ];
        $service = $this->getPoshmarkService();

        $container = [];
        $itemResponse = file_get_contents(DATA_DIR . '/item_response_1.json');
        $xsrfResponse = <<<'HTML'
<html>
<head>
<meta name="x_csrf_token" id="csrftoken" content="XYZ_TOKEN_ABC" />
</head>
<body>
<p>blah blah blah</p>
<div id=""></div>
</body></html>
HTML;

        $mockClient = $this->getMockGuzzleClient([
            new Response(200, ['Content-Type' => 'application/json'], $itemResponse),
            new Response(200, ['Content-Type' => 'text/html'], $xsrfResponse),
            new Response(200, ['Content-Type' => 'application/json'], '{"success": true}'),
        ], $container);
        $service->setGuzzleClient($mockClient);

        $expectedContent = '{"post":{"catalog":{"category_features":["02002f3cd97b4edf70005784"],"category":' .
            '"07008c10d97b4e1245005764","department":"01008c10d97b4e1245005764","department_obj":' .
            '{"id":"01008c10d97b4e1245005764","display":"Men","slug":"Men"},"category_obj":' .
            '{"id":"07008c10d97b4e1245005764","display":"Shirts","slug":"Shirts"},"category_feature_objs":' .
            '[{"id":"02002f3cd97b4edf70005784","display":"Sweatshirts & Hoodies","slug":"Sweatshirts_&_Hoodies"}]},' .
            '"colors":["Red","Gray"],"inventory":{"nfs_reason":"s","status_changed_at":"2020-05-05T21:41:05-07:00",' .
            '"size_quantity_revision":4,"size_quantities":[{"size_id":"M","quantity_available":1,' .
            '"quantity_reserved":0,"quantity_sold":0,"size_ref":64,"size_obj":{"id":"M","display":"M",' .
            '"display_with_size_set":"M"},"size_set_tags":["standard"]}],"status":"available","multi_item":false},' .
            '"price_amount":{"val":"134.00","currency_code":"USD","currency_symbol":"$"},"original_price_amount":' .
            '{"val":"99.0","currency_code":"USD","currency_symbol":"$"},"title":"Cool title!!! 7","description":' .
            '"Great condition, nice University sweatshirt with hood.","brand":"Champion","condition":"not_nwt",' .
            '"cover_shot":{"id":"5ac186a59b112e81ae0be4e2"},"pictures":[],"seller_private_info":{}}}';

        $expectedHeaders = [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Accept-Language' => 'en-US,en;q=0.5',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) snap Chromium/81.0.4044.138 Chrome/81.0.4044.138 Safari/537.36',
            'Accept-Encoding' => 'gzip',
            'Referer' => 'https://poshmark.com/edit-listing/abc123def456789',
            'Cookie' => '_csrf=123; __ssid=abc; exp=word%20space; ui=%7B%22dh%22%3A%22a%22%2C%22em%22%3A%22b%22%2C%22uid%22%3A%22c%22%2C%22fn%22%3A%22John%2520Smith%22%7D; _uetsid=foo_y; _web_session=aa; jwt=bb',
            'Content-Type' => 'application/json',
            'X-XSRF-TOKEN' => 'XYZ_TOKEN_ABC',
        ];

        $result = $service->updateItemRequest('abc123def456789', $newFields);

        $this->assertTrue($result);
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
