<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseProduct extends Model
{
    //
    /** @use HasFactory<\Database\Factories\WarehouseProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['warehouse_id', 'product_id', 'stock'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
