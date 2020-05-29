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

namespace PHPosh\Exception;

/**
 * An unspecific PHPosh specific exception. This is used for more extreme cases such as:
 * - the HTTP connection failed
 * - the server responded with a >= 400 or >= 500 HTTP status code
 * - the server said you are logged out
 * - the data returned by the server was not in the format which was expected.
 *
 * This is also the base class for any PHPosh exception, so if you did want to capture all possible exceptions
 * coming from this library, you may `catch (DataException $e)` and will get everything.
 *
 * The "code" property ('getCode') on this class will mirror the HTTP status code, if a response was actually achieved.
 * If no HTTP Response was achieved, the code will be 101.
 */
class DataException extends \Exception
{
}
