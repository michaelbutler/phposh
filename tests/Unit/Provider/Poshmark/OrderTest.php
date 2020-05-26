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
use PHPosh\Provider\Poshmark\Order;
use PHPosh\Provider\Poshmark\Price;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \PHPosh\Provider\Poshmark\Order
 */
class OrderTest extends TestCase
{
    public function testAllOrderMethods(): void
    {
        $price1 = Price::fromString('$1.00');
        $price2 = Price::fromString('$2.00');
        $price3 = Price::fromString('$3.00');
        $price4 = Price::fromString('$4.00');
        $items = [
            (new Item())->setId('aa'),
            (new Item())->setId('bb'),
        ];
        $order = new Order();
        $order->setImageUrl('a')
            ->setTitle('b')
            ->setSize('c')
            ->setId('d')
            ->setShippingLabelPdf('e')
            ->setTaxes($price1)
            ->setEarnings($price2)
            ->setPoshmarkFee($price3)
            ->setOrderTotal($price4)
            ->setOrderDate(new \DateTime('2020-04-21'))
            ->setItems($items)
            ->setItemCount(2)
            ->setOrderStatus(Order::STATUS_DELIVERED)
            ->setBuyerUsername('f')
            ->setUrl('g')
            ->setBuyerAddress('h')
        ;

        $this->assertSame('a', $order->getImageUrl());
        $this->assertSame('b', $order->getTitle());
        $this->assertSame('c', $order->getSize());
        $this->assertSame('d', $order->getId());
        $this->assertSame('e', $order->getShippingLabelPdf());
        $this->assertSame('f', $order->getBuyerUsername());
        $this->assertSame('g', $order->getUrl());
        $this->assertSame('h', $order->getBuyerAddress());

        $this->assertSame('1.00', $order->getTaxes()->getAmount());
        $this->assertSame('2.00', $order->getEarnings()->getAmount());
        $this->assertSame('3.00', $order->getPoshmarkFee()->getAmount());
        $this->assertSame('4.00', $order->getOrderTotal()->getAmount());

        $this->assertSame(Order::STATUS_DELIVERED, $order->getOrderStatus());
        $this->assertSame('2020-04-21', $order->getOrderDate()->format('Y-m-d'));

        $items = $order->getItems();
        $this->assertCount(2, $items);
        $this->assertSame(2, $order->getItemCount());
    }
}
