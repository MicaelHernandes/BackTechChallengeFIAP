<?php

namespace Domain\Workshop\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id'           => ['required', 'integer', 'exists:customers,id'],
            'vehicle_id'            => ['required', 'integer', 'exists:vehicles,id'],
            'complaint'             => ['required', 'string', 'max:2000'],
            'mechanic_user_id'      => ['nullable', 'integer', 'exists:users,id'],

            // Itens solicitados na abertura — opcionais, sem preço (orçamento oficial só na etapa de diagnóstico).
            'services'              => ['sometimes', 'array'],
            'services.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'services.*.quantity'   => ['required', 'integer', 'min:1'],
            'parts'                 => ['sometimes', 'array'],
            'parts.*.part_id'       => ['required', 'integer', 'exists:parts,id'],
            'parts.*.quantity'      => ['required', 'integer', 'min:1'],
        ];
    }
}
