<?php

namespace Domain\Workshop\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderServiceModel extends Model
{
    use SoftDeletes;

    protected $table = 'order_services';

    protected $fillable = [
        'status',
        'customer_id',
        'vehicle_id',
        'complaint',
        'mechanic_user_id',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function budget(): HasOne
    {
        return $this->hasOne(BudgetModel::class, 'os_id');
    }

    public function serviceItems(): HasMany
    {
        return $this->hasMany(OsServiceItemModel::class, 'os_id');
    }

    public function partItems(): HasMany
    {
        return $this->hasMany(OsPartItemModel::class, 'os_id');
    }

    public function requestedServiceItems(): HasMany
    {
        return $this->hasMany(OsRequestedServiceItemModel::class, 'os_id');
    }

    public function requestedPartItems(): HasMany
    {
        return $this->hasMany(OsRequestedPartItemModel::class, 'os_id');
    }
}
