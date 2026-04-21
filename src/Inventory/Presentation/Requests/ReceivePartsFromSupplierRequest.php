<?php

namespace Domain\Inventory\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePartsFromSupplierRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'supplier_name'                      => ['required', 'string', 'max:150'],
            'items'                              => ['required', 'array', 'min:1'],
            'items.*.part_request_item_id'       => ['required', 'integer'],
            'items.*.quantity_received'          => ['required', 'integer', 'min:1'],
        ];
    }
}
