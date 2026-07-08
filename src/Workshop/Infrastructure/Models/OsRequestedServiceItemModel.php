<?php

namespace Domain\Workshop\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsRequestedServiceItemModel extends Model
{
    protected $table = 'os_requested_service_items';

    protected $fillable = [
        'os_id',
        'service_id',
        'service_name',
        'quantity',
    ];

    public function orderService(): BelongsTo
    {
        return $this->belongsTo(OrderServiceModel::class, 'os_id');
    }
}
