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

class Inventory
{
    /** @var string Available status such as "available" */
    private $status;

    /** @var string Not-for-sale reason, e.g. "s" */
    private $nfsReason;

    /** @var array Complex nest of raw data, multi-item, size-objects, statuses, etc. */
    private $rawData;
}
