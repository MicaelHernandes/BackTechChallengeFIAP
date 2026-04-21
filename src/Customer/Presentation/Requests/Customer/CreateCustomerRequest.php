<?php

namespace Domain\Customer\Presentation\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'min:11', 'max:18'],
            'email'    => ['required', 'email:rfc,dns', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'address'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
