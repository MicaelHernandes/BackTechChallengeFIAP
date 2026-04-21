<?php

namespace Domain\Workshop\Domain\Events;

use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Enums\OsStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OsStatusUpdatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OrderService $orderService,
        public readonly OsStatus $previousStatus,
        public readonly string $customerEmail,
        public readonly string $customerName,
    ) {}
}
