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
 * Represents an Order of Item(s).
 */
class Order
{
    /** @var string Right after purchase, still needs to ship */
    public const STATUS_SOLD = 'Sold';

    /** @var string Printed label but not picked up yet */
    public const STATUS_PENDING_SCAN = 'Pending Shipment Scan';

    /** @var string Shipped and scanned */
    public const STATUS_SHIPPED = 'Shipped';

    /** @var string Delivered and confirmed by buyer */
    public const STATUS_DELIVERED = 'Delivered';

    /** @var string Poshmark Identifier */
    private $id;

    /** @var string Order title */
    private $title;

    /** @var Price Order total */
    private $orderTotal;

    /** @var Price Money made (after fees, etc.) */
    private $earnings;

    /** @var Price */
    private $poshmarkFee;

    /** @var Price */
    private $taxes;

    /** @var \DateTime Date ordered */
    private $orderDate;

    /** @var string Size purchased (single-item order only) */
    private $size;

    /** @var string Url to order details */
    private $url;

    /** @var string Url to item picture (the first item) */
    private $imageUrl;

    /** @var string Username of buyer */
    private $buyerUsername;

    /** @var string Address of buyer (currently not supported, as Poshmark doesn't make this easy to access) */
    private $buyerAddress;

    /** @var string Order status */
    private $orderStatus;

    /** @var Item[] Order items */
    private $items;

    /** @var int Number of items in order */
    private $itemCount;

    /** @var string Url to the shipping label PDF download */
    private $shippingLabelPdf;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): Order
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Order
    {
        $this->title = $title;

        return $this;
    }

    public function getOrderTotal(): Price
    {
        return $this->orderTotal;
    }

    public function setOrderTotal(Price $orderTotal): Order
    {
        $this->orderTotal = $orderTotal;

        return $this;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setSize(string $size): Order
    {
        $this->size = $size;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): Order
    {
        $this->url = $url;

        return $this;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): Order
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getBuyerUsername(): string
    {
        return $this->buyerUsername;
    }

    public function setBuyerUsername(string $buyerUsername): Order
    {
        $this->buyerUsername = $buyerUsername;

        return $this;
    }

    public function getBuyerAddress(): string
    {
        return $this->buyerAddress;
    }

    public function setBuyerAddress(string $buyerAddress): Order
    {
        $this->buyerAddress = $buyerAddress;

        return $this;
    }

    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(string $orderStatus): Order
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    public function getOrderDate(): \DateTime
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTime $orderDate): Order
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    public function getEarnings(): Price
    {
        return $this->earnings;
    }

    public function setEarnings(Price $earnings): Order
    {
        $this->earnings = $earnings;

        return $this;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param Item[] $items
     */
    public function setItems(array $items): Order
    {
        $this->items = $items;

        return $this;
    }

    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    public function setItemCount(int $itemCount): Order
    {
        $this->itemCount = $itemCount;

        return $this;
    }

    public function getPoshmarkFee(): Price
    {
        return $this->poshmarkFee;
    }

    public function setPoshmarkFee(Price $poshmarkFee): Order
    {
        $this->poshmarkFee = $poshmarkFee;

        return $this;
    }

    public function getTaxes(): Price
    {
        return $this->taxes;
    }

    public function setTaxes(Price $taxes): Order
    {
        $this->taxes = $taxes;

        return $this;
    }

    public function getShippingLabelPdf(): string
    {
        return $this->shippingLabelPdf;
    }

    public function setShippingLabelPdf(string $shippingLabelPdf): Order
    {
        $this->shippingLabelPdf = $shippingLabelPdf;

        return $this;
    }
}
