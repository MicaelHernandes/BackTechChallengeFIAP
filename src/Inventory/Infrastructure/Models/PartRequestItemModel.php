<?php

namespace Domain\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartRequestItemModel extends Model
{
    protected $table = 'part_request_items';

    protected $fillable = [
        'part_request_id',
        'part_id',
        'part_name',
        'quantity_requested',
        'quantity_provided',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_provided'  => 'integer',
    ];

    public function partRequest(): BelongsTo
    {
        return $this->belongsTo(PartRequestModel::class, 'part_request_id');
    }
}
