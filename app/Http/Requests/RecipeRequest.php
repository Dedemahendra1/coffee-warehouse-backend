<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\UnitConverter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $recipeId = $this->route('recipe');
        $recipeId = is_object($recipeId) ? $recipeId->id : $recipeId;

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
                Rule::unique('recipes', 'product_id')->ignore($recipeId),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ingredient_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000000000'],
            'items.*.unit' => ['required', 'string', 'max:20'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $productId = $this->input('product_id');
                $items = $this->input('items');

                if (!is_array($items)) {
                    return;
                }

                $ingredientIds = collect($items)
                    ->pluck('ingredient_id')
                    ->filter()
                    ->unique()
                    ->values();

                $units = Product::whereIn('id', $ingredientIds)->pluck('unit', 'id');

                foreach ($items as $index => $item) {
                    $ingredientId = $item['ingredient_id'] ?? null;

                    if ($ingredientId !== null && (string) $ingredientId === (string) $productId) {
                        $validator->errors()->add(
                            "items.$index.ingredient_id",
                            'Bahan baku tidak boleh sama dengan produk yang memiliki resep ini.'
                        );
                        continue;
                    }

                    $ingredientUnit = $ingredientId !== null ? $units->get($ingredientId) : null;
                    $unit = $item['unit'] ?? null;

                    if ($ingredientUnit && $unit && !UnitConverter::isConvertible($unit, $ingredientUnit)) {
                        $validator->errors()->add(
                            "items.$index.unit",
                            "Satuan $unit tidak kompatibel dengan satuan produk ({$ingredientUnit})."
                        );
                    }
                }
            },
        ];
    }
}
