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

use PHPosh\Provider\Poshmark\Price;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \PHPosh\Provider\Poshmark\Price
 */
class PriceTest extends TestCase
{
    public function providerForPrices(): array
    {
        return [
            [
                '$45.12',
                [
                    'amount' => '45.12',
                    'currencyCode' => 'USD',
                    'currencySymbol' => '$',
                    'asString' => '$45.12',
                ],
            ],
            [
                ' 14.00 EUR ',
                [
                    'amount' => '14.00',
                    'currencyCode' => 'EUR',
                    'currencySymbol' => '€',
                    'asString' => '€14.00',
                ],
            ],
            [
                '€ nothing ABC ',
                [
                    'amount' => '0.00',
                    'currencyCode' => 'USD',
                    'currencySymbol' => '$',
                    'asString' => '$0.00',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerForPrices
     */
    public function testPriceModel(string $inputString, array $expectedProps): void
    {
        $price1 = Price::fromString($inputString);

        $this->assertSame($expectedProps['amount'], $price1->getAmount());
        $this->assertSame($expectedProps['currencyCode'], $price1->getCurrencyCode());
        $this->assertSame($expectedProps['currencySymbol'], $price1->getCurrencySymbol());
        $this->assertSame($expectedProps['asString'], (string) $price1);
    }
}
