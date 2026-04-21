<?php

namespace Domain\Catalog\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartModel extends Model
{
    use SoftDeletes;

    protected $table = 'parts';

    protected $fillable = [
        'code',
        'name',
        'description',
        'unit_price',
        'stock_quantity',
        'minimum_stock',
        'unit',
    ];

    protected $casts = [
        'unit_price'     => 'float',
        'stock_quantity' => 'integer',
        'minimum_stock'  => 'integer',
    ];
}
