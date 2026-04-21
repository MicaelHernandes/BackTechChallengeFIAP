<?php

namespace Domain\Catalog\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:150'],
            'base_price'        => ['required', 'numeric', 'min:0'],
            'description'       => ['nullable', 'string'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
