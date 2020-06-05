# PHPosh

[![Build Status](https://api.travis-ci.org/michaelbutler/phposh.svg?branch=master)](https://travis-ci.org/michaelbutler/phposh)
[![Code Coverage](https://scrutinizer-ci.com/g/michaelbutler/phposh/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/michaelbutler/phposh/?branch=master)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/michaelbutler/phposh/blob/master/LICENSE)
[![Latest Commit](https://img.shields.io/github/last-commit/michaelbutler/phposh/master)](https://github.com/michaelbutler/phposh/commits/master)
[![Coded in PhpStorm](https://img.shields.io/badge/Coded%20in-PhpStorm-blueviolet)](https://jetbrains.com/phpstorm)

PHP composer package to interact with Poshmark's website and APIs. Currently this uses your browser cookie information to do this.

## Requirements

- `php >= 7.1`

## Supported API

- [getItems](#getItems)
- [getItem](#getItem)
- [getOrderSummaries](#getOrderSummaries)
- [getOrderDetail](#getOrderDetail)
- [updateItem](#updateItem)

## Usage:

Add the composer package to your project.

```sh
# TODO: This is not in packagist yet
composer require michaelbutler/phposh
```


### Usage & Examples

Setup the service object that is used in all method calls:

```php
<?php

require_once 'vendor/autoload.php';

/*
 * The way this works is, it needs the cookie data from your logged-in Poshmark browser session.
 * Simple way to get this is:
 * - Log in to www.poshmark.com
 * - Press Ctrl/Command + Shift + e (Firefox)
 *      - In Chrome, Ctrl/Command + Shift + i then click Network tab
 * - Refresh the page
 * - Right click the very top web request in the list and choose Copy as cURL
 * - Paste into a Text Document and then find the information after `Cookie:`
 * - If you ever get an error, repeat the steps above to get the latest cookie data.
 */

$cookieString = "ps=....; _csrf=....; ...";
$pmService = new \PHPosh\Provider\Poshmark\PoshmarkService($cookieString);
```

Then you can use that `$pmService` object to make further calls to get or change data; read on.

### getItems

Retrieve all active (unsold) items in your closet:

```php
$allItems = $pmService->getItems();

foreach ($allItems as $item) {
    echo sprintf("itemId: %s - %s (%s)\n", $item->getId(), $item->getTitle(), $item->getPrice());
}

// Print the raw data array as provided by Poshmark
print_r($allItems[0]->getRawData());
```

List items for another user:

```php
$userUuid = 'abc123def456....'; // userUuid can be found in the HTML code of a user's closet web page
$username = 'coolshop';
$allItems = $pmService->getItems($userUuid, $username);

foreach ($allItems as $item) {
    echo sprintf("itemId: %s - %s (%s)\n", $item->getId(), $item->getTitle(), $item->getPrice());
}
```

### getItem

Retrieve a single item by its identifier (id). 

```php
// Use the itemId found via getItems or from an order
$item = $pmService->getItem('abc123def456....');

echo sprintf("itemId: %s - %s (%s)\n", $item->getId(), $item->getTitle(), $item->getPrice());

// The item's raw data element gives you all possible data provided by Poshmark,
// as a nested array map.
$rawData = $item->getRawData();

echo "Department: " . $rawData['department']['display'] . "\n";
echo "Num. Shares: " . $rawData['aggregates']['shares'] . "\n";

print_r($rawData);
```

### getOrderSummaries

Retrieve a list of your order summaries:

```php
// Get 50 most recent orders.
// Not all details are available in the order summaries.
$orders = $pmService->getOrderSummaries(50);

foreach ($orders as $order) {
    echo sprintf("orderId: %s - %s (%s)\n", $order->getId(), $order->getTitle(), $order->getBuyerUsername());
    echo sprintf("Status: %s, Num. Items: %d\n", $order->getOrderStatus(), $order->getItemCount());
}
```

Note that an "Order Summary" does not include full details about an order. It includes:
- Order title
- Sale price
- Size (single item orders only)
- Buyer username
- Order status
- Thumbnail (of first item only)

### getOrderDetail

To get all details about an order:

```php
// Following example above
$orderId = $orders[0]->getId();
$details = $pmService->getOrderDetail($orderId);

echo "\n";
echo sprintf("Order Title: %s\n", $details->getTitle());
echo sprintf("Buyer: %s\n", $details->getBuyerUsername());
echo sprintf("Total: %s\n", $details->getOrderTotal());
echo sprintf("Earnings: %s\n", $details->getEarnings());

echo "Items:\n";

foreach ($details->getItems() as $index => $item) {
    echo sprintf("%d: %s [%s] (%s)\n", $index + 1, $item->getTitle(), $item->getSize(), $item->getPrice());
}
```

### updateItem

Edit and post changes (e.g. listing price or item description) to an item's data:

```php
$itemFields = [
    // Only the below 4 fields are currently supported. Send at least one, multiple supported.
    'title' => 'Calvin Klein Jeans',
    'price' => '29.00 USD', // also "$29.00" is supported
    'description' => 'Great condition very comfortable jeans. One small tear on the left front pocket',
    'brand' => 'Calvin Klein',
];

try {
    $result = $pmService->updateItem('abc123def456...', $itemFields);
} catch (\PHPosh\Exception\DataException $e) {
    echo "ERROR: Item abc123def456... failed to update!! " . $e->getMessage();
    exit(1); 
}

echo "SUCCESS: Item abc123def456... updated!!\n";
```

## Contributing

This is a very early version of this library, help is welcome.

Things that are needed:

- More Poshmark functionality
- Better authentication mechanism

## License

MIT
