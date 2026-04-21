<?php

namespace Domain\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartRequestModel extends Model
{
    protected $table = 'part_requests';

    protected $fillable = [
        'status',
        'requested_by_user_id',
        'os_id',
        'notes',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PartRequestItemModel::class, 'part_request_id');
    }
}
