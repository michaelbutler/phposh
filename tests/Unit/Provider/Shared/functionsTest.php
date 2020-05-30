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

namespace PHPoshTests\Unit\Provider\Shared;

use function PHPosh\Shared\log_error;
use PHPUnit\Framework\TestCase;

/**
 * @covers ::\PHPosh\Shared\log_error()
 * @covers \PHPosh\Shared\log_error()
 *
 * @internal
 */
class functionsTest extends TestCase
{
    /** @var null|string Original error_log ini setting to reset after test */
    private $originalErrorLog;

    /** @var string Path to temporary error log file */
    private $tempErrorLog;

    protected function setUp()
    {
        parent::setUp();
        $this->originalErrorLog = ini_get('error_log') ?: null;
        $this->tempErrorLog = tempnam('/tmp', 'phposh-test');
        ini_set('error_log', $this->tempErrorLog);
    }

    protected function tearDown()
    {
        parent::tearDown();
        ini_set('error_log', $this->originalErrorLog);
    }

    public function testLogError(): void
    {
        log_error('[Testing error log]');
        $contents = file_get_contents($this->tempErrorLog);
        $this->assertRegExp('/\[Testing error log\]/', $contents);
    }
}
