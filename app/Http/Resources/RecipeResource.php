<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'thumbnail' => $this->product->thumbnail,
                'unit' => $this->product->unit,
                'price' => $this->product->price,
                'category_id' => $this->product->category_id,
                'category' => $this->product->category ? [
                    'id' => $this->product->category->id,
                    'name' => $this->product->category->name,
                ] : null,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'ingredient_id' => $item->ingredient_id,
                'quantity' => round((float) $item->quantity, 3),
                'unit' => $item->unit,
                'ingredient' => $item->ingredient ? [
                    'id' => $item->ingredient->id,
                    'name' => $item->ingredient->name,
                    'thumbnail' => $item->ingredient->thumbnail,
                    'unit' => $item->ingredient->unit,
                    'price' => $item->ingredient->price,
                    'stock' => $this->stock($item->ingredient),
                ] : null,
            ])),
            'total_items' => $this->whenLoaded('items', fn () => $this->items->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function stock(Product $ingredient): float
    {
        if ($ingredient->relationLoaded('warehouses')) {
            return round((float) $ingredient->warehouses->sum('pivot.stock'), 3);
        }

        return round((float) $ingredient->getWarehouseProductStock(), 3);
    }
}
