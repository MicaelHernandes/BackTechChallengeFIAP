<?php

namespace Domain\Customer\Presentation\Resources;

use Domain\Customer\Domain\Entities\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        return [
            'id'       => $customer->getId(),
            'name'     => $customer->getName(),
            'document' => $customer->getDocument()->formatted(),
            'email'    => $customer->getEmail(),
            'phone'    => $customer->getPhone(),
            'address'  => $customer->getAddress(),
        ];
    }
}
