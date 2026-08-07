<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionProduct extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['transaction_id', 'product_id', 'quantity', 'price', 'sub_total'];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
