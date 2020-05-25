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

use PHPosh\Provider\Poshmark\DataParser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPosh\Provider\Poshmark\DataParser
 *
 * @internal
 */
class DataParserTest extends TestCase
{
    public function testParseOneItemResponseJson(): void
    {
        $dataParser = new DataParser();
        $data = file_get_contents(DATA_DIR . '/item_response_1.json');
        $item = $dataParser->parseOneItemResponseJson(json_decode($data, true));
        $this->assertSame('5de18684a6e3ea2a8a0ba67a', $item->getId());
        $this->assertSame('Great condition, nice University sweatshirt with hood.', $item->getDescription());
        $this->assertSame('Arizona U Tigers pull over hoodie', $item->getTitle());
        $this->assertSame('$39.00', (string) $item->getPrice());
        $this->assertSame('$99.00', (string) $item->getOrigPrice());
        $this->assertSame('M', $item->getSize());
    }
}
