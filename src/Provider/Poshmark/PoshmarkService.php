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

namespace PHPosh\Provider\Poshmark;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use PHPosh\Exception\AuthenticationException;
use PHPosh\Exception\CookieException;
use PHPosh\Exception\GeneralException;
use PHPosh\Exception\ItemNotFoundException;
use PHPosh\Exception\OrderNotFoundException;
use function PHPosh\Shared\log_error;
use PHPosh\Shared\Provider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use sndsgd\Str;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Browser-Cookie based PoshmarkProvider.
 * The way this works is, it needs the cookie data from your logged-in Poshmark browser session.
 * Simple way to do this is:
 * - Log in to www.poshmark.com
 * - Press Ctrl/Command + Shift + K (Firefox) or Ctrl/Command + Shift + J (Chrome)
 * - Type document.cookie and press Enter
 * - Copy and Save that entire value shown between the quotes
 * - $pmProvider = new PoshmarkProvider("<paste the cookie data here>");
 * - $items = $pmProvider->getItems()
 * - If & when you get an error, repeat the steps above to get the latest cookie data.
 */
class PoshmarkService implements Provider
{
    /** @var string URL upon which all requests are based */
    public const BASE_URL = 'https://poshmark.com';

    /** @var array Standard options for the Guzzle client */
    private const DEFAULT_OPTIONS = [
        'timeout' => 5,
        'base_uri' => self::BASE_URL,
    ];

    /** @var array Standard headers to send on each request */
    private const DEFAULT_HEADERS = [
        'Accept' => 'application/json, text/javascript, */*; q=0.01',
        'Accept-Language' => 'en-US,en;q=0.5',
        'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) snap Chromium/81.0.4044.138 Chrome/81.0.4044.138 Safari/537.36',
        'Accept-Encoding' => 'gzip',
        'Referer' => '', // Replace with actual
        'Cookie' => '', // Replace with actual
    ];

    /** @var string An HTTP referrer (referer) to use if not specified otherwise */
    private const DEFAULT_REFERRER = 'https://poshmark.com/feed';

    /** @var array Map of cookies to send. Used to exclude unnecessary cruft */
    private const COOKIE_WHITELIST = [
        '_csrf' => true,
        '__ssid' => true,
        'exp' => true,
        'ui' => true,
        '_uetsid' => true,
        '_web_session' => true,
        'jwt' => true,
    ];

    /** @var Client */
    private $guzzleClient;

    /** @var array Map of cookies (name => value) to use */
    private $cookies = [];

    /** @var string Human readable username. Auto populated from cookie data */
    private $username;

    /** @var string Email address (from cookie) */
    private $email;

    /** @var string Full name of user (from cookie) */
    private $fullname;

    /** @var string Poshmark user id of user (from cookie) */
    private $pmUserId;

    /** @var string Timestamp when the cookie was pasted in to this system. If too old, it might not work... */
    private $cookieTimestamp;

    /**
     * Client constructor. Same options as Guzzle.
     *
     * @param string $cookieCode Copy+Pasted version of document.cookie on https://poshmark.com
     * @param array  $config     Optional Guzzle config overrides (See Guzzle docs for Client constructor)
     */
    public function __construct($cookieCode, array $config = [])
    {
        $config = array_merge($config, static::DEFAULT_OPTIONS);
        $this->setGuzzleClient(new Client($config));
        $this->cookies = $this->parseCookiesFromString($cookieCode);
        $this->setupUserFromCookies($this->cookies);
    }

    /**
     * @return $this
     */
    public function setGuzzleClient(Client $client): self
    {
        $this->guzzleClient = $client;

        return $this;
    }

    /**
     * Get All closet items of a user. Returned items will be sorted by item id. This needs to make multiple HTTP
     * requests to Poshmark, not in parallel. Only 20 per page is currently supported, so this will take about 3.5
     * seconds for every 100 items there are, or about 30 seconds for every 1000.
     *
     * @param string $usernameUuid Uuid of user. If empty, will use yourself (from cookie).
     * @param string $username     Display username of user. If empty, will use yourself (from cookie).
     *
     * @throws AuthenticationException
     *
     * @return Item[]
     */
    public function getItems(string $usernameUuid = '', string $username = ''): array
    {
        if (!$usernameUuid) {
            $usernameUuid = $this->pmUserId;
        }
        if (!$username) {
            $username = $this->username;
        }
        $dataParser = $this->getDataParser();

        // Set a sane upper bound; 250 * 20 = 5000 max items to get
        $iterations = 250;
        $maxId = null;
        $items = [];
        while ($iterations > 0) {
            $loopItems = $this->getItemsByMaxId($usernameUuid, $username, $maxId);
            if (!$loopItems || empty($loopItems['data'])) {
                break;
            }
            foreach ($loopItems['data'] as $item) {
                // Convert each raw json to an Item object
                $items[] = $dataParser->parseOneItemResponseJson($item);
            }
            $maxId = ($loopItems['more']['next_max_id'] ?? null);
            if ($maxId <= 0) {
                // No next id signifies finished listing
                break;
            }
            $maxId = (string) $maxId;
            --$iterations;

            // Sleep 100ms
            usleep(100000);

            if ($iterations % 10) {
                // Every 10th iteration sleep an additional amount
                usleep(200000);
            }
        }

        usort($items, static function ($a, $b) {
            // sort array items by their item ids
            return strcmp($a->getId(), $b->getId());
        });

        return $items;
    }

    /**
     * Get data on a single item.
     *
     * @param string $poshmarkItemId Poshmark Item Id
     *
     * @throws ItemNotFoundException
     */
    public function getItem(string $poshmarkItemId): Item
    {
        if (!$poshmarkItemId) {
            throw new \InvalidArgumentException('$poshmarkItemId must be non-empty');
        }
        $parser = $this->getDataParser();
        $headers = static::DEFAULT_HEADERS;
        $headers['Referer'] = static::DEFAULT_REFERRER;
        $headers['Cookie'] = $this->getCookieHeader();

        $url = '/vm-rest/posts/%s?app_version=2.55&_=%s';
        $url = sprintf($url, rawurlencode($poshmarkItemId), (string) microtime(true));

        $response = $this->makeRequest('get', $url, [
            'headers' => $headers,
        ]);

        if (!$response) {
            throw new ItemNotFoundException("Item {$poshmarkItemId} not found");
        }

        $data = $this->getJsonData($response);

        return $parser->parseOneItemResponseJson($data);
    }

    /**
     * Update data of a single item. Must provide title, description, price, and brand in $itemFields for this to work.
     *
     * Example:
     *
     * @param string $poshmarkItemId PoshmarkId for the item
     * @param array  $itemFields     New item data -- will replace the old data. All fields are optional but you must at least
     *                               provide one.
     *                               Only these fields currently supported:
     *                               [
     *                               'title' => 'New title',
     *                               'description' => 'New description',
     *                               'price' => '4.95 USD', // Price, with currency code (will default to USD)
     *                               'brand' => 'Nike', // brand name
     *                               ]
     *
     * @throws AuthenticationException
     *
     * @return bool returns true on success, throws exception on failure
     */
    public function updateItemRequest(string $poshmarkItemId, array $itemFields): bool
    {
        if (!$poshmarkItemId) {
            throw new \InvalidArgumentException('$poshmarkItemId must be non-empty');
        }
        $itemObj = $this->getItem($poshmarkItemId);
        if (!$itemObj) {
            throw new GeneralException('404 Item not found');
        }

        $newItemData = Helper::createItemDataForUpdate($itemFields, $itemObj->getRawData());

        $postBody = [
            'post' => $newItemData,
        ];
        $postBody = json_encode($postBody);

        $headers = static::DEFAULT_HEADERS;
        $headers['Referer'] = self::BASE_URL . '/edit-listing/' . $poshmarkItemId;
        $headers['Cookie'] = $this->getCookieHeader();
        $headers['Content-Type'] = 'application/json';

        $headers['X-XSRF-TOKEN'] = $this->getXsrfTokenForEditItem($poshmarkItemId);
        usleep(200000);

        $url = '/vm-rest/posts/%s';
        $url = sprintf($url, rawurlencode($poshmarkItemId));

        $response = $this->makeRequest('post', $url, [
            'body' => $postBody,
            'headers' => $headers,
        ]);

        if (!$response) {
            return false;
        }

        // Check response code
        $this->getHtmlData($response);

        return true;
    }

    /**
     * Get full details on an order, by parsing the item details page.
     *
     * @param string $orderId Poshmark OrderID
     */
    public function getOrderDetails(string $orderId): Order
    {
        $headers = static::DEFAULT_HEADERS;
        $headers['Referer'] = static::DEFAULT_REFERRER;
        $headers['Cookie'] = $this->getCookieHeader();
        $headers['Accept'] = 'text/html';

        $dataParser = $this->getDataParser();

        $url = '/order/sales/%s?_=%s';
        $url = sprintf($url, $orderId, (string) microtime(true));

        $response = $this->makeRequest('get', $url, [
            'headers' => $headers,
        ]);

        if (!$response) {
            throw new OrderNotFoundException("Order {$orderId} was not found.");
        }

        $html = $this->getHtmlData($response);

        $items = $this->getOrderItems($html);

        return $dataParser->parseFullOrderResponseHtml($orderId, $html, $items);
    }

    /**
     * Get a list of order summaries. These won't have full details populated. Sorted by newest first.
     *
     * @param int $limit Max number of orders to get. Maximum allowed: 10000
     *
     * @throws AuthenticationException|GeneralException
     *
     * @return Order[]
     */
    public function getOrderSummaries(int $limit = 100): array
    {
        if ($limit < 0 || $limit > 10000) {
            throw new GeneralException('Limit must be between 1 and 10,000 orders');
        }
        $orders = [];
        $numOrders = 0;
        $maxId = '';
        $iterations = 0;
        while ($iterations++ < 100) {  // Safe guard to limit infinite loops
            [$loopOrders, $maxId] = $this->getOrdersLoop($maxId);
            if ($loopOrders && is_array($loopOrders)) {
                $orders[] = $loopOrders;
                $numOrders += count($loopOrders);
            }
            if (!$loopOrders || $maxId < 0 || $numOrders >= $limit) {
                break;
            }
        }

        if ($orders !== []) {
            $orders = array_merge(...$orders);
        }

        if ($numOrders > $limit) {
            $orders = array_slice($orders, 0, $limit);
        }

        return $orders;
    }

    /**
     * Makes multiple web requests to get each individual item from an order page.
     */
    protected function getOrderItems(string $html): array
    {
        $crawler = new Crawler($html);
        $contentNode = $crawler->filter('.order-main-con');
        $itemNodes = $contentNode->filter('.listing-details .rw');

        $itemUrls = $itemNodes->each(static function (Crawler $node, $i) {
            return $node->filter('a')->first()->attr('href');
        });
        $items = [];
        foreach ($itemUrls as $url) {
            $id = DataParser::parseItemIdFromUrl($url);
            $items[] = $this->getItem($id);
        }

        return $items;
    }

    /**
     * Get a page of closet items by using max_id. Not publicly accessible, use getItems() instead.
     *
     * @param string $usernameUuid Obfuscated user id
     * @param string $username     Human readable username
     * @param mixed  $max_id       Max ID param for pagination. If null, get first page.
     *
     * @throws AuthenticationException
     *
     * @return array ['data' => [...], 'more' => [...]]
     */
    protected function getItemsByMaxId(string $usernameUuid, string $username, $max_id = null): array
    {
        $headers = static::DEFAULT_HEADERS;
        $headers['Referer'] = static::DEFAULT_REFERRER;
        $headers['Cookie'] = $this->getCookieHeader();

        $url = '/vm-rest/users/%s/posts?app_version=2.55&format=json&username=%s&nm=cl_all&summarize=true&_=%s';
        if ($max_id) {
            $url .= '&max_id=' . $max_id;
        }
        $url = sprintf(
            $url,
            rawurlencode($usernameUuid),
            rawurlencode($username),
            (string) microtime(true)
        );

        $response = $this->makeRequest('get', $url, [
            'headers' => $headers,
        ]);

        return $this->getJsonData($response) ?: [];
    }

    /**
     * Returns the cookie array.
     */
    protected function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Convert back the internal cookie map to a string for use in a Cookie: HTTP header.
     */
    protected function getCookieHeader(): string
    {
        // TODO: Memoize this
        $cookiesToSend = [];
        foreach ($this->getCookies() as $name => $value) {
            if (isset(static::COOKIE_WHITELIST[$name])) {
                $cookiesToSend[$name] = $value;
            }
        }

        return http_build_query($cookiesToSend, '', '; ', PHP_QUERY_RFC3986);
    }

    /**
     * Get a CSRF token (sometimes called XSRF token) for the user, necessary for updates.
     *
     * @param string $poshmarkItemId Item id
     */
    protected function getXsrfTokenForEditItem(string $poshmarkItemId): string
    {
        $headers = static::DEFAULT_HEADERS;
        $headers['Referer'] = static::DEFAULT_REFERRER;
        $headers['Cookie'] = $this->getCookieHeader();
        $headers['Accept'] = 'text/html';

        $url = '/edit-listing/%s?_=%s';
        $url = sprintf($url, rawurlencode($poshmarkItemId), (string) microtime(true));

        $response = $this->makeRequest('get', $url, [
            'headers' => $headers,
        ]);

        if (!$response) {
            return '';
        }

        $html = $this->getHtmlData($response);

        $crawler = new Crawler($html);
        $node = $crawler->filter('#csrftoken')->eq(0);
        if (!$node) {
            throw new GeneralException('Failed to find a CSRF token on the page');
        }

        return (string) $node->attr('content');
    }

    /**
     * @param string $maxId Max ID for pagination
     *
     * @return array [Order[], $nextMaxId]
     */
    protected function getOrdersLoop(string $maxId = ''): array
    {
        $headers = static::DEFAULT_HEADERS;
        $headers['Referer'] = static::DEFAULT_REFERRER;
        $headers['Cookie'] = $this->getCookieHeader();
        $headers['Accept'] = 'application/json';
        $headers['X-Requested-With'] = 'XMLHttpRequest';

        $dataParser = $this->getDataParser();

        $url = '/order/sales?_=%s';
        $url = sprintf($url, (string) microtime(true));

        if ('' !== $maxId) {
            $url .= '&max_id=' . $maxId;
        }

        $response = $this->makeRequest('get', $url, [
            'headers' => $headers,
        ]);

        if (!$response) {
            return [null, null];
        }

        $json = $this->getJsonData($response);

        $nextMaxId = $json['max_id'] ?? -1;
        if (!$nextMaxId) {
            $nextMaxId = -1;
        }

        $html = $json['html'];

        return [
            $html ? $dataParser->parseOrdersPagePartialResponse($html) : [],
            $nextMaxId,
        ];
    }

    protected function getDataParser(): DataParser
    {
        return new DataParser();
    }

    /**
     * Wrapper function for making any HTTP requests. Exceptions will be caught and error logged,
     * and null will be returned in that case.
     *
     * @param string|UriInterface $url
     */
    private function makeRequest(string $method, $url, array $guzzleOptions): ?ResponseInterface
    {
        $method = strtolower($method);
        if (!in_array($method, ['get', 'post'], true)) {
            throw new \InvalidArgumentException('$method must be one of: get, post');
        }

        try {
            /** @see Client::__call */
            $response = $this->guzzleClient->{$method}($url, $guzzleOptions);
        } catch (TransferException $e) {
            log_error(
                sprintf(
                    'Got exception when trying to request URL %s: %s ' .
                    'Perhaps you need to recreate your input cookie string.',
                    $url,
                    $e->getMessage()
                )
            );

            return null;
        }

        return $response;
    }

    /**
     * @throws AuthenticationException
     */
    private function getJsonData(ResponseInterface $response): array
    {
        if (200 !== $response->getStatusCode()) {
            throw new AuthenticationException('Poshmark: Received non-200 status', $response->getStatusCode());
        }

        $content = trim($response->getBody()->getContents());

        if (!isset($content[0]) || '{' !== $content[0]) {
            throw new AuthenticationException('Poshmark: Unexpected json body', $response->getStatusCode());
        }

        $data = json_decode($content, true);
        if (!$data || !\is_array($data)) {
            throw new AuthenticationException('Poshmark: Unexpected json body', $response->getStatusCode());
        }

        return $data;
    }

    /**
     * @param string $cookieCode Raw cookie string such as "a=1; b=foo; _c=hello world;"
     *
     * @return array Map of cookie name => cookie value (already decoded)
     */
    private function parseCookiesFromString(string $cookieCode): array
    {
        $cookieCode = trim($cookieCode);
        if (Str::beginsWith($cookieCode, '"')) {
            // remove double quotes
            $cookieCode = trim($cookieCode, '"');
        } elseif (Str::beginsWith($cookieCode, "'")) {
            $cookieCode = trim($cookieCode, "'");
        }
        $cookies = [];
        parse_str(
            strtr($cookieCode, ['&' => '%26', '+' => '%2B', ';' => '&']),
            $cookies
        );

        return $cookies;
    }

    /**
     * Sets interval variables (user, email, etc.) from the cookies array.
     *
     * @param array $cookies Cookie name=>value hashmap
     *
     * @throws CookieException When a required cookie is not provided
     */
    private function setupUserFromCookies(array $cookies): void
    {
        foreach (static::COOKIE_WHITELIST as $cookieKey => $bool) {
            if (!isset($cookies[$cookieKey]) || '' === $cookies[$cookieKey]) {
                throw new CookieException(
                    sprintf('Required cookie %s was not supplied', $cookieKey)
                );
            }
        }

        $ui = $cookies['ui'];

        $userData = json_decode($ui, true);
        $this->username = $userData['dh'];
        $this->email = $userData['em'];
        $this->pmUserId = $userData['uid'];
        $this->fullname = urldecode($userData['fn']);
        $this->cookieTimestamp = time();
    }

    /**
     * Get HTML body, and do some basic error checking.
     *
     * @throws AuthenticationException
     */
    private function getHtmlData(ResponseInterface $response): string
    {
        if (200 !== $response->getStatusCode()) {
            throw new AuthenticationException('Poshmark: Received non-200 status', $response->getStatusCode());
        }

        $content = trim($response->getBody()->getContents());
        if ('' === $content) {
            throw new AuthenticationException('Poshmark: Unexpected HTML body', $response->getStatusCode());
        }

        return $content;
    }
}
