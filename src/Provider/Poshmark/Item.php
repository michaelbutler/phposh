<?php

namespace PHPosh\Provider\Poshmark;

use PHPosh\Shared\BaseItem;

/**
 * Data object representing an item on Poshmark.
 * If you need the raw unmodified data from Poshmark, call getRawData().
 */
class Item implements BaseItem
{
    /** @var string Represents new with tags condition */
    public const CONDITION_NEW_WITH_TAGS = 'nwt';

    /** @var string Represents not new with tags condition */
    public const CONDITION_NOT_NEW_WITH_TAGS = 'not_nwt';

    /** @var string Unique identifier of the item on Poshmark */
    private $id;

    /** @var string Title */
    private $title;

    /** @var string Description */
    private $description;

    /** @var string Condition string. either "not_nwt" or "nwt" */
    private $condition;

    /** @var string Main Category ID */
    private $category;

    /** @var string Main Department ID */
    private $department;

    /** @var string[] List of category feature IDs */
    private $category_features = [];

    /** @var Inventory Inventory information object */
    private $inventory;

    /** @var string[] List of colors */
    private $colors = [];

    /** @var string Brand name */
    private $brand;

    /** @var Price Current price (object) */
    private $price;

    /** @var Price Original price, if any (object) */
    private $origPrice;

    /** @var string Full URL to image of item (cover_shot) */
    private $imageUrl;

    /** @var string ID of cover shot photo */
    private $coverShotId;

    /** @var array Array of additional (not cover) photos */
    private $pictures;

    /** @var array Optional private seller info (such as sku) */
    private $sellerPrivateInfo;

    /** @var \DateTime When this item was originally created/listed */
    private $createdAt;

    /** @var string Size of item, e.g. "X-Large", "34 x 32", or "3" */
    private $size;

    /** @var string URL to item page on the provider site */
    private $externalUrl;

    /** @var int Which provider this item belongs to */
    private $providerType;

    /** @var array Original raw data from provider */
    private $rawData = [];

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
     * @return Item
     */
    public function setTitle(string $title): Item
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Item
     */
    public function setDescription(string $description): Item
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     *
     * @return Item
     */
    public function setBrand(string $brand): Item
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @return Price
     */
    public function getPrice(): Price
    {
        return $this->price;
    }

    /**
     * @param Price $price
     *
     * @return Item
     */
    public function setPrice(Price $price): Item
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return Price
     */
    public function getOrigPrice(): Price
    {
        return $this->origPrice;
    }

    /**
     * @param Price $origPrice
     *
     * @return Item
     */
    public function setOrigPrice(Price $origPrice): Item
    {
        $this->origPrice = $origPrice;
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
     * @return Item
     */
    public function setImageUrl(string $imageUrl): Item
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return Item
     */
    public function setCreatedAt(\DateTime $createdAt): Item
    {
        $this->createdAt = $createdAt;
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
     * @return Item
     */
    public function setSize(string $size): Item
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return string
     */
    public function getExternalUrl(): string
    {
        return $this->externalUrl;
    }

    /**
     * @param string $externalUrl
     *
     * @return Item
     */
    public function setExternalUrl(string $externalUrl): Item
    {
        $this->externalUrl = $externalUrl;
        return $this;
    }

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
     * @return Item
     */
    public function setId(string $id): Item
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return array
     */
    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    /**
     * @param array $customFields
     *
     * @return Item
     */
    public function setCustomFields(array $customFields): Item
    {
        $this->customFields = $customFields;
        return $this;
    }

    /**
     * @return int
     */
    public function getProviderType(): int
    {
        return $this->providerType;
    }

    /**
     * @param int $providerType One of the Provider::PROVIDER_TYPE_* constants
     * @return Item
     */
    public function setProviderType(int $providerType): Item
    {
        $this->providerType = $providerType;
        return $this;
    }

    /**
     * @return array
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * @param array $rawData
     *
     * @return Item
     */
    public function setRawData(array $rawData): Item
    {
        $this->rawData = $rawData;
        return $this;
    }
}
