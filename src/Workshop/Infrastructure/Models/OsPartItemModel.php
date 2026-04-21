<?php

namespace Domain\Workshop\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsPartItemModel extends Model
{
    protected $table = 'os_part_items';

    protected $fillable = [
        'os_id',
        'part_id',
        'part_name',
        'quantity',
        'unit_price',
    ];

    public function orderService(): BelongsTo
    {
        return $this->belongsTo(OrderServiceModel::class, 'os_id');
    }
}
