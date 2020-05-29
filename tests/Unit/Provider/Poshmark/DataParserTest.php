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

use PHPosh\Provider\Poshmark\DataParser;
use PHPosh\Provider\Poshmark\Item;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @covers \PHPosh\Provider\Poshmark\DataParser
 *
 * @internal
 */
class DataParserTest extends TestCase
{
    public function providerForOneItemResponse(): array
    {
        $data = file_get_contents(DATA_DIR . '/item_response_1.json');
        $data = json_decode($data, true);
        $notNestedData = $data['data'];

        return [
            [$data],
            [$notNestedData],
        ];
    }

    /**
     * @dataProvider providerForOneItemResponse
     */
    public function testParseOneItemResponseJson(array $dataArray): void
    {
        $dataParser = new DataParser();
        $item = $dataParser->parseOneItemResponseJson($dataArray);
        $this->assertSame('5de18684a6e3ea2a8a0ba67a', $item->getId());
        $this->assertSame('Great condition, nice University sweatshirt with hood.', $item->getDescription());
        $this->assertSame('Arizona U Tigers pull over hoodie', $item->getTitle());
        $this->assertSame('$39.00', (string) $item->getPrice());
        $this->assertSame('$99.00', (string) $item->getOrigPrice());
        $this->assertSame('M', $item->getSize());
    }

    public function testParseOneItemResponseJsonWithBadDateCreated(): void
    {
        $parseFailureDate = '_-';
        $dataParser = new DataParser();
        $data = file_get_contents(DATA_DIR . '/item_response_1.json');
        $data = json_decode($data, true);
        $data['data']['created_at'] = $parseFailureDate;

        $item = $dataParser->parseOneItemResponseJson($data);
        $oldDate = new \DateTime();
        $oldDate->setTimestamp(strtotime('-15 second'));
        $this->assertTrue($item->getCreatedAt() > $oldDate);
    }

    public function testParseOrdersPagePartialResponse(): void
    {
        $body_data1 = file_get_contents(DATA_DIR . '/order_summaries_html_1.json');
        $data = json_decode($body_data1, true);
        $html = $data['html'];
        $dataParser = new DataParser();
        $orders = $dataParser->parseOrdersPagePartialResponse($html);
        $this->assertCount(100, $orders);

        // Assert expected values of first order
        $firstOrder = $orders[0];
        $this->assertSame('6adf3c045971a75920f59970', $firstOrder->getId());
        $this->assertSame('Nike XL Running Shorts Blue 857785 Flex 2in1 7" Sh', $firstOrder->getTitle());
        $this->assertSame('$28.00', (string) $firstOrder->getOrderTotal());
        $this->assertSame(1, $firstOrder->getItemCount());
        $this->assertSame('Shopper60006528', $firstOrder->getBuyerUsername());
        $this->assertSame('Sold', $firstOrder->getOrderStatus());
        $this->assertSame('XL', $firstOrder->getSize());
        $this->assertSame(
            'https://poshmark.com/order/sales/6adf3c045971a75920f59970',
            $firstOrder->getUrl()
        );
        $this->assertSame(
            'https://di2ponv0v5otw.cloudfront.net/posts/2020/05/19/88f554842a3246d9664f4b28/m_d6384dbe02197983cce2d37e.jpeg',
            $firstOrder->getImageUrl()
        );

        // Assert expected values of the 10th order, which has multiple items
        $anotherOrder = $orders[9];
        $this->assertSame('a0b7e27bf3bed3bc3ac0d59c', $anotherOrder->getId());
        $this->assertSame('Bundle of Navy Blue Canali Pants 34x31... and 1 more item', $anotherOrder->getTitle());
        $this->assertSame('$68.00', (string) $anotherOrder->getOrderTotal());
        $this->assertSame(2, $anotherOrder->getItemCount());
        $this->assertSame('Shopper51573027', $anotherOrder->getBuyerUsername());
        $this->assertSame('Delivered', $anotherOrder->getOrderStatus());
        $this->assertSame('', $anotherOrder->getSize());
        $this->assertSame(
            'https://poshmark.com/order/sales/a0b7e27bf3bed3bc3ac0d59c',
            $anotherOrder->getUrl()
        );
        $this->assertSame(
            'https://di2ponv0v5otw.cloudfront.net/posts/2020/05/03/d733b4dbb962181ef4ff2427/m_2c87479ff9013c842e15b196.jpeg',
            $anotherOrder->getImageUrl()
        );
    }

    public function testParseFullOrderResponseHtmlOnMultiItemOrder(): void
    {
        $body_html = file_get_contents(DATA_DIR . '/order_details_1.html');
        $dataParser = new DataParser();
        // Build dummy items array; the contents of this doesn't matter here, except first item's imageUrl
        $items = [];
        $items[] = new Item();
        $items[] = new Item();
        $items[0]->setImageUrl('https://foo.net/image.webp');
        $order = $dataParser->parseFullOrderResponseHtml('abc123def456', $body_html, $items);
        $this->assertSame('abc123def456', $order->getId());
        $this->assertSame('Order abc123def456 (2 items)', $order->getTitle());
        $this->assertSame('$28.00', (string) $order->getOrderTotal());
        $this->assertSame('$26.30', (string) $order->getEarnings());
        $this->assertSame('$4.70', (string) $order->getPoshmarkFee());
        $this->assertSame('$3.24', (string) $order->getTaxes());
        $this->assertSame('2020-05-24', $order->getOrderDate()->format('Y-m-d'));
        $this->assertSame('', $order->getSize());
        $this->assertSame('https://foo.net/image.webp', $order->getImageUrl());
        $this->assertSame('coolbuyer123', $order->getBuyerUsername());
        $this->assertSame('https://poshmark.com/order/sales/abc123def456', $order->getUrl());
        $this->assertSame(2, $order->getItemCount());
        $this->assertSame('Sold', $order->getOrderStatus());
        $this->assertSame(
            'https://poshmark.com/order/sales/abc123def456/download_shipping_label_link',
            $order->getShippingLabelPdf()
        );
    }

    public function providerForItemUrls(): array
    {
        return [
            ['www.foo.net/item/whatever/Nike-Shorts-abc123def456bbbaaaccc', 'abc123def456bbbaaaccc'],
            ['/item/whatever/Nike-Shorts-Not-Really-An-id', 'id'],
            ['', ''],
        ];
    }

    /**
     * @dataProvider providerForItemUrls
     */
    public function testParseItemIdFromUrl(string $listingUrl, string $expectedId): void
    {
        $this->assertSame($expectedId, DataParser::parseItemIdFromUrl($listingUrl));
    }

    public function testParseOrderPrices(): void
    {
        $html = file_get_contents(DATA_DIR . '/order_details_1.html');
        $crawler = new Crawler($html);
        [$orderTotal, $poshmarkFee, $earnings, $tax] = DataParser::parseOrderPrices($crawler);
        $this->assertSame('$28.00', (string) $orderTotal);
        $this->assertSame('$4.70', (string) $poshmarkFee);
        $this->assertSame('$26.30', (string) $earnings);
        $this->assertSame('$3.24', (string) $tax);
    }
}
