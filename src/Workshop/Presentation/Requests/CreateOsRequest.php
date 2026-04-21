<?php

namespace Domain\Workshop\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id'      => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id'       => ['required', 'integer', 'exists:vehicles,id'],
            'complaint'        => ['required', 'string', 'max:2000'],
            'mechanic_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
