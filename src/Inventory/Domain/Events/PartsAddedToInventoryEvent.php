<?php

namespace Domain\Inventory\Domain\Events;

use Domain\Inventory\Domain\Entities\PartRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartsAddedToInventoryEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PartRequest $partRequest,
        public readonly string $supplierName,
    ) {}
}
