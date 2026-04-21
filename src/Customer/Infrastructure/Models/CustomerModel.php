<?php

namespace Domain\Customer\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'address',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'customer_id');
    }
}
