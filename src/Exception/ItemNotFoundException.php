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
 * Covers the case where an item lookup was requested but it wasn't found (e.g. 404).
 */
class ItemNotFoundException extends \Exception
{
}
