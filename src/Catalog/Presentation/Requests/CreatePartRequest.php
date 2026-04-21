<?php

namespace Domain\Catalog\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePartRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'           => ['required', 'string', 'max:30'],
            'name'           => ['required', 'string', 'max:150'],
            'unit_price'     => ['required', 'numeric', 'min:0'],
            'description'    => ['nullable', 'string'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'minimum_stock'  => ['nullable', 'integer', 'min:0'],
            'unit'           => ['nullable', 'string', 'max:10'],
        ];
    }
}
