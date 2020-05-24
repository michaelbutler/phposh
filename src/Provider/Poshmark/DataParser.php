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

use Symfony\Component\DomCrawler\Crawler;

class DataParser
{
    /**
     * Convert the JSON item data into an Item object.
     *
     * @param array $data Full JSON web response as a data array
     *
     * @throws \Exception
     */
    public function parseOneItemResponseJson(array $data): Item
    {
        if (!isset($data['title']) && isset($data['data'])) {
            $itemData = $data['data'];
        } else {
            $itemData = $data;
        }
        $base_url = PoshmarkService::BASE_URL;
        $newItem = new Item();
        $dt = new \DateTime($itemData['created_at']);

        $currentPrice = new Price();
        $currentPrice->setCurrencyCode($itemData['price_amount']['currency_code'] ?? 'USD')
            ->setAmount($itemData['price_amount']['val'] ?? '0.00')
        ;

        $origPrice = new Price();
        $origPrice->setCurrencyCode($itemData['original_price_amount']['currency_code'] ?? 'USD')
            ->setAmount($itemData['original_price_amount']['val'] ?? '0.00')
        ;

        $newItem->setBrand($itemData['brand'] ?? '')
            ->setCreatedAt($dt)
            ->setPrice($currentPrice)
            ->setOrigPrice($origPrice)
            ->setSize($itemData['size'] ?: '')
            ->setId($itemData['id'] ?: '')
            ->setTitle($itemData['title'] ?: 'Unknown')
            ->setDescription($itemData['description'])
            ->setExternalUrl($base_url . '/listing/item-' . $itemData['id'])
            ->setImageUrl($itemData['picture_url'] ?: '')
            ->setRawData($itemData)
        ;

        return $newItem;
    }

    /**
     * @return Order[]
     */
    public function parseOrdersPagePartialResponse(string $html): array
    {
        $crawler = new Crawler($html);
        $items = $crawler->filter('a.item');
        $retItems = $items->each(static function (Crawler $node, $i) {
            $order = new Order();
            $price = Price::fromString($node->filter('.price .value')->first()->text());
            $path = $node->attr('href');
            $parts = explode('/', $path);
            $id = array_pop($parts);
            // Multi-item orders will not have a size here
            $sizeNode = $node->filter('.size .value');
            $count = 1;
            $badge = $node->filter('.badge-con .badge');
            if ($badge->count() > 0) {
                $count = $badge->first()->text();
            }
            $order->setTitle($node->filter('.title')->eq(0)->text())
                ->setId($id)
                ->setUrl(PoshmarkService::BASE_URL . $path)
                ->setImageUrl($node->filter('img.item-pic')->first()->attr('src'))
                ->setSize($sizeNode->count() > 0 ? $sizeNode->first()->text() : '')
                ->setBuyerUsername($node->filter('.seller .value')->first()->text())
                ->setOrderTotal($price)
                ->setOrderStatus($node->filter('.status .value')->first()->text())
                ->setItemCount($count)
            ;

            return $order;
        });

        return $retItems;
    }

    /**
     * This parses the full order details and also makes HTTP requests for the full individual item details.
     *
     * @param string $html  HTML content of the order details page
     * @param Item[] $items Items to assign to the order
     */
    public function parseFullOrderResponseHtml(string $orderId, string $html, array $items): Order
    {
        $crawler = new Crawler($html);
        $contentNode = $crawler->filter('.order-main-con');

        $order = new Order();
        $order->setItems($items);

        [$orderTotal, $poshmarkFee, $earnings, $tax] = Helper::parseOrderPrices($contentNode);

        $count = count($items);
        $multiItemOrder = $count > 1;

        $title = $multiItemOrder ?
            sprintf('Order %s (%d items)', $orderId, count($items)) :
            $items[0]->getTitle();

        $dateAndUser = $contentNode->filter('.order-details')->text();
        $matches = [];
        preg_match(
            '/Date:([A-Z\d+-]+2[0-9]{3})[^\#]+\#:([a-z0-9_-]{24}).*Buyer: (.*)/i',
            $dateAndUser,
            $matches
        );
        $orderDate = $matches[1] ?? null;
        $buyerName = $matches[3] ?? 'Unknown';

        $orderDate = new \DateTime($orderDate);

        $orderStatus = $contentNode->filter('.status-desc')->text();

        $matches = [];
        preg_match('/Status:([A-Z ]+)/i', $orderStatus, $matches);
        $orderStatus = trim($matches[1] ?? 'Unknown');

        $order->setTitle($title)
            ->setId($orderId)
            ->setUrl(PoshmarkService::BASE_URL . '/order/sales/' . $orderId)
            ->setImageUrl($items[0]->getImageUrl())
            ->setSize('')
            ->setBuyerUsername($buyerName)
            ->setOrderTotal($orderTotal)
            ->setEarnings($earnings)
            ->setPoshmarkFee($poshmarkFee)
            ->setTaxes($tax)
            ->setOrderStatus($orderStatus)
            ->setItemCount($count)
            ->setOrderDate($orderDate)
        ;

        $order->setShippingLabelPdf(
            sprintf('%s/order/sales/%s/download_shipping_label_link', PoshmarkService::BASE_URL, $orderId)
        );

        return $order;
    }
}