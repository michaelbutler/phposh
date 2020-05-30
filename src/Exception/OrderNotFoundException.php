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
 * Handles cases where an order request is made, but it couldn't be found, or there was a problem getting it.
 */
class OrderNotFoundException extends DataException
{
}
