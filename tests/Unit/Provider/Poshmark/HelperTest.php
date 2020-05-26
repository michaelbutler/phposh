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

use PHPosh\Provider\Poshmark\Helper;
use PHPosh\Provider\Poshmark\Price;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \PHPosh\Provider\Poshmark\Helper
 */
class HelperTest extends TestCase
{
    public function testCreateItemDataForUpdate(): void
    {
        $rawData = file_get_contents(DATA_DIR . '/item_response_1.json');
        $rawData = json_decode($rawData, true);
        $rawData = $rawData['data'];
        $itemPostData = Helper::createItemDataForUpdate([
            'title' => 'Cool new title!!!',
            'price' => '15.00 USD',
        ], $rawData);
        $this->assertSame(['Red', 'Gray'], $itemPostData['colors']);
        $this->assertSame('Cool new title!!!', $itemPostData['title']);
        $this->assertSame('Great condition, nice University sweatshirt with hood.', $itemPostData['description']);
    }

    public function testCreateItemDataForUpdateWithPriceObj(): void
    {
        $rawData = file_get_contents(DATA_DIR . '/item_response_1.json');
        $rawData = json_decode($rawData, true);
        $rawData = $rawData['data'];
        $newPrice = new Price();
        $newPrice->setAmount('21.00')
            ->setCurrencyCode('USD')
        ;
        $itemPostData = Helper::createItemDataForUpdate([
            'description' => 'Cool new description ZOMG',
            'price' => $newPrice,
        ], $rawData);
        $this->assertSame(['Red', 'Gray'], $itemPostData['colors']);
        $this->assertSame('Arizona U Tigers pull over hoodie', $itemPostData['title']);
        $this->assertSame('Cool new description ZOMG', $itemPostData['description']);
    }

    public function testCreateItemDataForUpdateWithOnlyBrandChange(): void
    {
        $rawData = file_get_contents(DATA_DIR . '/item_response_1.json');
        $rawData = json_decode($rawData, true);
        $rawData = $rawData['data'];
        $itemPostData = Helper::createItemDataForUpdate([
            'brand' => 'Reebok',
        ], $rawData);
        $this->assertSame('Reebok', $itemPostData['brand']);

        $fullExpectedData = [
            'catalog' => [
                'category_features' => [
                    0 => '02002f3cd97b4edf70005784',
                ],
                'category' => '07008c10d97b4e1245005764',
                'department' => '01008c10d97b4e1245005764',
                'department_obj' => [
                    'id' => '01008c10d97b4e1245005764',
                    'display' => 'Men',
                    'slug' => 'Men',
                ],
                'category_obj' => [
                    'id' => '07008c10d97b4e1245005764',
                    'display' => 'Shirts',
                    'slug' => 'Shirts',
                ],
                'category_feature_objs' => [
                    0 => [
                        'id' => '02002f3cd97b4edf70005784',
                        'display' => 'Sweatshirts & Hoodies',
                        'slug' => 'Sweatshirts_&_Hoodies',
                    ],
                ],
            ],
            'colors' => [
                0 => 'Red',
                1 => 'Gray',
            ],
            'inventory' => [
                'nfs_reason' => 's',
                'status_changed_at' => '2020-05-05T21:41:05-07:00',
                'size_quantity_revision' => 4,
                'size_quantities' => [
                    0 => [
                        'size_id' => 'M',
                        'quantity_available' => 1,
                        'quantity_reserved' => 0,
                        'quantity_sold' => 0,
                        'size_ref' => 64,
                        'size_obj' => [
                            'id' => 'M',
                            'display' => 'M',
                            'display_with_size_set' => 'M',
                        ],
                        'size_set_tags' => [
                            0 => 'standard',
                        ],
                    ],
                ],
                'status' => 'available',
                'multi_item' => false,
            ],
            'price_amount' => [
                'val' => '39.0',
                'currency_code' => 'USD',
                'currency_symbol' => '$',
            ],
            'original_price_amount' => [
                'val' => '99.0',
                'currency_code' => 'USD',
                'currency_symbol' => '$',
            ],
            'title' => 'Arizona U Tigers pull over hoodie',
            'description' => 'Great condition, nice University sweatshirt with hood.',
            'brand' => 'Reebok',
            'condition' => 'not_nwt',
            'cover_shot' => [
                'id' => '5ac186a59b112e81ae0be4e2',
            ],
            'pictures' => [
            ],
            'seller_private_info' => (object) [
            ],
        ];

        $this->assertSame(
            json_encode($fullExpectedData),
            json_encode($itemPostData),
            'Expecting both nested arrays to match.'
        );
    }
}
