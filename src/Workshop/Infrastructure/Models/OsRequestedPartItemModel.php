<?php

namespace Domain\Workshop\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsRequestedPartItemModel extends Model
{
    protected $table = 'os_requested_part_items';

    protected $fillable = [
        'os_id',
        'part_id',
        'part_name',
        'quantity',
    ];

    public function orderService(): BelongsTo
    {
        return $this->belongsTo(OrderServiceModel::class, 'os_id');
    }
}
