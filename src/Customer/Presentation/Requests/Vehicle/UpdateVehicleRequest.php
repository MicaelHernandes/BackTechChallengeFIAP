<?php

namespace Domain\Customer\Presentation\Requests\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plate' => ['required', 'string', 'max:8'],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:100'],
            'year'  => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'color' => ['required', 'string', 'max:40'],
        ];
    }
}
