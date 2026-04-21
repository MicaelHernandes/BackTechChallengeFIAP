<?php

namespace Domain\Inventory\Domain\Events;

use Domain\Inventory\Domain\Entities\PartRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartOutOfStockEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PartRequest $partRequest,
    ) {}
}
