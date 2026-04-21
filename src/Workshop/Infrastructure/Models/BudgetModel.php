<?php

namespace Domain\Workshop\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetModel extends Model
{
    protected $table = 'budgets';

    protected $fillable = [
        'os_id',
        'total_services',
        'total_parts',
        'total_amount',
        'notes',
    ];

    public function orderService(): BelongsTo
    {
        return $this->belongsTo(OrderServiceModel::class, 'os_id');
    }
}
