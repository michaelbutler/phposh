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

/**
 * Utility/helper functions for Poshmark Service.
 */
class Helper
{
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
     *
     * @return array map of key=value pairs that can be sent as the POST body for an update item request
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
            'seller_private_info' => $rawItemData['seller_private_info'] ?? new \stdClass(),
        ];
    }
}
