<?php

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
