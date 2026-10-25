<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'ingredient_id',
        'quantity',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
        ];
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Product::class, 'ingredient_id');
    }
}
