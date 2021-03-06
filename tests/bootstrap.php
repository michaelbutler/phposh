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

define('DATA_DIR', __DIR__ . '/Data');

// Set our own error log file during unit tests
// so that we don't clutter the test output with example errors.
ini_set(
    'error_log',
    '/tmp/phposh-tests-' . microtime(true) . random_int(9, 9999)
);
