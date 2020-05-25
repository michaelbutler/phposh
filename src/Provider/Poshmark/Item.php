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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Item
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Item
    {
        $this->description = $description;

        return $this;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): Item
    {
        $this->brand = $brand;

        return $this;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function setPrice(Price $price): Item
    {
        $this->price = $price;

        return $this;
    }

    public function getOrigPrice(): Price
    {
        return $this->origPrice;
    }

    public function setOrigPrice(Price $origPrice): Item
    {
        $this->origPrice = $origPrice;

        return $this;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): Item
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): Item
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setSize(string $size): Item
    {
        $this->size = $size;

        return $this;
    }

    public function getExternalUrl(): string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(string $externalUrl): Item
    {
        $this->externalUrl = $externalUrl;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): Item
    {
        $this->id = $id;

        return $this;
    }

    public function getProviderType(): int
    {
        return $this->providerType;
    }

    /**
     * @param int $providerType One of the Provider::PROVIDER_TYPE_* constants
     */
    public function setProviderType(int $providerType): Item
    {
        $this->providerType = $providerType;

        return $this;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(array $rawData): Item
    {
        $this->rawData = $rawData;

        return $this;
    }
}
