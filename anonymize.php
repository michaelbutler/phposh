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

// automatically change/randomize all the Hex based identifiers (a-f, 0-9)

$inputFile = $argv[1] ?? null;
if (!$inputFile) {
    echo "ERROR: Input file argument required as argument 1.\n";
    exit(1);
}

$contents = file_get_contents($inputFile);
if (!$contents) {
    echo "ERROR: Input file {$inputFile} is empty.\n";
    exit(1);
}

// First find all hex ids and create a map
$hexIds = [];

$matches = [];
preg_match_all('/([a-f0-9]{20,40})/', $contents, $matches);

if (empty($matches[1])) {
    echo "No ids found. Exiting.\n";
    exit(0);
}

$allIds = array_unique($matches[1]);
$allIds = array_flip($allIds);

foreach ($allIds as $origId => $null) {
    $allIds[$origId] = substr(hash('sha512', $origId), 0, 24);
}

$contents = str_replace(array_keys($allIds), array_values($allIds), $contents);

// CUSTOM REPLACEMENTS
if (true) {
//    $matches = [];
//    preg_match_all('/Buyer: <span class=\\\"value\\\">([^<]+)<\/span>/i', $contents, $matches);
//    print_r($matches);
    $contents = preg_replace_callback('/Buyer: <span class=\\\"value\\\">([^<]+)<\/span>/i', static function ($matches) {
        return 'Buyer: <span class=\\"value\\">Shopper' . random_int(1000, 99999999) . '</span>';
    }, $contents);
}

echo $contents;
