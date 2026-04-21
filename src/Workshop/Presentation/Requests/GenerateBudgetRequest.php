<?php

namespace Domain\Workshop\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBudgetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'services'             => ['present', 'array'],
            'services.*.service_id'=> ['required', 'integer', 'exists:services,id'],
            'services.*.quantity'  => ['required', 'integer', 'min:1'],
            'parts'                => ['present', 'array'],
            'parts.*.part_id'      => ['required', 'integer', 'exists:parts,id'],
            'parts.*.quantity'     => ['required', 'integer', 'min:1'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }
}
