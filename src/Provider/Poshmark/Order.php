<?php

namespace PHPosh\Provider\Poshmark;

use PHPosh\Exception\AuthenticationException;
use PHPosh\Exception\ItemNotFoundException;

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

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return Order
     */
    public function setId(string $id): Order
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Order
     */
    public function setTitle(string $title): Order
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return Price
     */
    public function getOrderTotal(): Price
    {
        return $this->orderTotal;
    }

    /**
     * @param Price $orderTotal
     *
     * @return Order
     */
    public function setOrderTotal(Price $orderTotal): Order
    {
        $this->orderTotal = $orderTotal;
        return $this;
    }

    /**
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * @param string $size
     *
     * @return Order
     */
    public function setSize(string $size): Order
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return Order
     */
    public function setUrl(string $url): Order
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @param string $imageUrl
     *
     * @return Order
     */
    public function setImageUrl(string $imageUrl): Order
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getBuyerUsername(): string
    {
        return $this->buyerUsername;
    }

    /**
     * @param string $buyerUsername
     *
     * @return Order
     */
    public function setBuyerUsername(string $buyerUsername): Order
    {
        $this->buyerUsername = $buyerUsername;
        return $this;
    }

    /**
     * @return string
     */
    public function getBuyerAddress(): string
    {
        return $this->buyerAddress;
    }

    /**
     * @param string $buyerAddress
     *
     * @return Order
     */
    public function setBuyerAddress(string $buyerAddress): Order
    {
        $this->buyerAddress = $buyerAddress;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    /**
     * @param string $orderStatus
     *
     * @return Order
     */
    public function setOrderStatus(string $orderStatus): Order
    {
        $this->orderStatus = $orderStatus;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getOrderDate(): \DateTime
    {
        return $this->orderDate;
    }

    /**
     * @param \DateTime $orderDate
     *
     * @return Order
     */
    public function setOrderDate(\DateTime $orderDate): Order
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    /**
     * @return Price
     */
    public function getEarnings(): Price
    {
        return $this->earnings;
    }

    /**
     * @param Price $earnings
     *
     * @return Order
     */
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
     *
     * @return Order
     */
    public function setItems(array $items): Order
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    /**
     * @param int $itemCount
     *
     * @return Order
     */
    public function setItemCount(int $itemCount): Order
    {
        $this->itemCount = $itemCount;
        return $this;
    }

    /**
     * @return Price
     */
    public function getPoshmarkFee(): Price
    {
        return $this->poshmarkFee;
    }

    /**
     * @param Price $poshmarkFee
     *
     * @return Order
     */
    public function setPoshmarkFee(Price $poshmarkFee): Order
    {
        $this->poshmarkFee = $poshmarkFee;
        return $this;
    }

    /**
     * @return Price
     */
    public function getTaxes(): Price
    {
        return $this->taxes;
    }

    /**
     * @param Price $taxes
     *
     * @return Order
     */
    public function setTaxes(Price $taxes): Order
    {
        $this->taxes = $taxes;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingLabelPdf(): string
    {
        return $this->shippingLabelPdf;
    }

    /**
     * @param string $shippingLabelPdf
     *
     * @return Order
     */
    public function setShippingLabelPdf(string $shippingLabelPdf): Order
    {
        $this->shippingLabelPdf = $shippingLabelPdf;
        return $this;
    }
}
