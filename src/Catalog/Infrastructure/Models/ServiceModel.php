<?php

namespace Domain\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceModel extends Model
{
    use SoftDeletes;

    protected $table = 'services';

    protected $fillable = [
        'name',
        'description',
        'base_price',
        'estimated_minutes',
        'is_active',
    ];

    protected $casts = [
        'base_price'        => 'float',
        'estimated_minutes' => 'integer',
        'is_active'         => 'boolean',
    ];
}
