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

use PHPosh\Provider\Poshmark\Item;
use PHPosh\Provider\Poshmark\Price;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \PHPosh\Provider\Poshmark\Item
 */
class ItemTest extends TestCase
{
    public function testAllItemMethods(): void
    {
        $price1 = Price::fromString('$12.34');
        $price2 = Price::fromString('$45.50');
        $item = new Item();
        $item->setImageUrl('a')
            ->setId('b')
            ->setPrice($price1)
            ->setOrigPrice($price2)
            ->setSize('c')
            ->setTitle('d')
            ->setRawData(['a' => 'b'])
            ->setDescription('e')
            ->setExternalUrl('f')
            ->setCreatedAt(new \DateTime('2020-05-24'))
            ->setBrand('g')
        ;
        $this->assertSame('a', $item->getImageUrl());
        $this->assertSame('b', $item->getId());
        $this->assertSame('c', $item->getSize());
        $this->assertSame('d', $item->getTitle());
        $this->assertSame('e', $item->getDescription());
        $this->assertSame('f', $item->getExternalUrl());
        $this->assertSame('g', $item->getBrand());
        $this->assertSame($price1, $item->getPrice());
        $this->assertSame($price2, $item->getOrigPrice());
        $this->assertSame(['a' => 'b'], $item->getRawData());
        $this->assertSame('2020-05-24 00:00:00', $item->getCreatedAt()->format('Y-m-d H:i:s'));
    }
}
