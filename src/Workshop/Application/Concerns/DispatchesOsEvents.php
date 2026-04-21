<?php

namespace Domain\Workshop\Application\Concerns;

use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Enums\OsStatus;
use Domain\Workshop\Domain\Events\OsStatusUpdatedEvent;
use Illuminate\Support\Facades\Event;

trait DispatchesOsEvents
{
    private function dispatchOsStatusUpdated(
        OrderService $os,
        OsStatus $previousStatus,
        CustomerRepositoryInterface $customerRepository,
    ): void {
        $customer = $customerRepository->findById($os->getCustomerId());

        if ($customer === null) {
            return;
        }

        Event::dispatch(new OsStatusUpdatedEvent(
            orderService: $os,
            previousStatus: $previousStatus,
            customerEmail: $customer->getEmail(),
            customerName: $customer->getName(),
        ));
    }
}
