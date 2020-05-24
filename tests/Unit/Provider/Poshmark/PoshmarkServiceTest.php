<?php

namespace PHPoshTests\Unit\Provider\Poshmark;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPosh\Provider\Poshmark\PoshmarkService;
use PHPUnit\Framework\TestCase;

class PoshmarkServiceTest extends TestCase
{
    /**
     * @return PoshmarkService
     */
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
        return "_csrf=123; __ssid=abc; exp=word space; ui=$ui; _uetsid=foo_y; " .
            '_derived_epik=foo_z; _web_session=aa; jwt=bb;';
    }

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
     * @param string $cookieStr
     * @param array $expectedCookies
     *
     * @dataProvider providerForCookieStrings
     */
    public function testCookieDecoding(string $cookieStr, array $expectedCookies): void
    {
        $poshmark = new PoshmarkService($cookieStr);

        $method = new \ReflectionMethod(PoshmarkService::class, 'getCookies');
        $method->setAccessible(true);

        $this->assertSame($expectedCookies, $method->invoke($poshmark));
    }

    /**
     * @return void
     */
    public function testGetItem(): void
    {
        $pmService = $this->getPoshmarkService();

        $body_data = file_get_contents(DATA_DIR . '/item_response_1.json');

        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, ['X-Test' => 'true'], $body_data),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $mockClient = new Client(['handler' => $handlerStack]);
        $pmService->setGuzzleClient($mockClient);

        $item = $pmService->getItem('abcdefg123456');

        // Assert Request was made as expected
        $firstRequest = array_pop($container);

        // Assert Response
        $this->assertSame('5de18684a6e3ea2a8a0ba67a', $item->getId());
        $this->assertSame('Arizona U Tigers pull over hoodie', $item->getTitle());
        $this->assertSame('Great condition, nice University sweatshirt with hood.', $item->getDescription());
    }
}
