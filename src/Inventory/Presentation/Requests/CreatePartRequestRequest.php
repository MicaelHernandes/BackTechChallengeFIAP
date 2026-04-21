<?php

namespace Domain\Inventory\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePartRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'os_id'              => ['nullable', 'integer'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.part_id'   => ['required', 'integer'],
            'items.*.quantity'  => ['required', 'integer', 'min:1'],
        ];
    }
}
