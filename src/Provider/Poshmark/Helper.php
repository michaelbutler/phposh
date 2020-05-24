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

/**
 * Utility/helper functions for Poshmark Service.
 */
class Helper
{
    /**
     * Auto-detect the item id given a listing URL.
     *
     * @param string $listingUrl Listing URL such as /listing/Red-Pants-Gap-Jeans-5eaa834be23448c3438d...
     *
     * @return string Just the item id, such as 5eaa834be23448c3438d
     */
    public static function parseItemIdFromUrl(string $listingUrl): string
    {
        $parts = explode('-', $listingUrl);

        return array_pop($parts) ?: '';
    }

    /**
     * @return array [Price, Price, Price, Price] (orderTotal, poshmarkFee, earnings, tax)
     */
    public static function parseOrderPrices(Crawler $contentNode): array
    {
        // Parse out price information using a regex
        $pricesInfo = trim($contentNode->filter('.price-details')->text());
        $matches = [];
        preg_match(
            '/[\D.]+([\d.]+)[\D.]+([\d.]+)[\D.]+([\d.]+)[\D]+([\d]+\.[\d]+)/i',
            $pricesInfo,
            $matches
        );
        $orderTotal = $matches[1] ?? '0.00';
        $position = mb_strpos($pricesInfo, $orderTotal);
        $symbol = mb_substr($pricesInfo, $position - 1, 1);
        $orderTotal = $symbol . $orderTotal;
        $poshmarkFee = $symbol . $matches[2] ?? '0.00';
        $earnings = $symbol . $matches[3] ?? '0.00';
        $tax = $symbol . $matches[4] ?? '0.00';

        $orderTotal = Price::fromString($orderTotal);
        $poshmarkFee = Price::fromString($poshmarkFee);
        $earnings = Price::fromString($earnings);
        $tax = Price::fromString($tax);

        return [$orderTotal, $poshmarkFee, $earnings, $tax];
    }

    /**
     * Take an array of user itemFields and rawItemData from Poshmark, and generate a valid array that may be used
     * in a POST update for editing that item (which will later be JSONified)
     * $itemFields should be an array like:
     * [
     *     'title' => 'New title',
     *     'description' => 'New description',
     *     'price' => '4.95 USD', // Price, with currency code (will default to USD)
     *                            // a Price object is also supported
     *     'brand' => 'Nike', // brand name
     * ]
     * The fields are all optional, only requested fields will be edited. However you must supply at least one.
     */
    public static function createItemDataForUpdate(array $itemFields, array $rawItemData): array
    {
        $colors = [];
        foreach ($rawItemData['colors'] as $arr) {
            $colors[] = $arr['name'];
        }

        if (isset($itemFields['price'])) {
            $newPrice = $itemFields['price'] instanceof Price ?
                $itemFields['price'] :
                Price::fromString($itemFields['price']);

            $newPrice = [
                'val' => $newPrice->getAmount(),
                'currency_code' => $newPrice->getCurrencyCode(),
                'currency_symbol' => $newPrice->getCurrencySymbol(),
            ];
        } else {
            $newPrice = $rawItemData['price_amount'];
        }

        $newTitle = $itemFields['title'] ?? $rawItemData['title'];
        $newDesc = $itemFields['description'] ?? $rawItemData['description'];
        $newBrand = $itemFields['brand'] ?? $rawItemData['brand'];

        return [
            'catalog' => $rawItemData['catalog'],
            'colors' => $colors,
            'inventory' => $rawItemData['inventory'],
            'price_amount' => $newPrice,
            'original_price_amount' => $rawItemData['original_price_amount'],
            'title' => $newTitle,
            'description' => $newDesc,
            'brand' => $newBrand,
            'condition' => $rawItemData['condition'],
            'cover_shot' => [
                'id' => $rawItemData['cover_shot']['id'],
            ],
            'pictures' => $rawItemData['pictures'] ?: [], // TODO make this work right
            'seller_private_info' => $rawItemData['seller_private_info'] ?: new \stdClass(),
        ];
    }
}
