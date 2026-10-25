<?php

namespace App\Repositories;

use App\Models\Recipe;

class RecipeRepository
{
    public function getAll()
    {
        return Recipe::with([
            'product.category',
            'items.ingredient.warehouses',
            'items.ingredient.category',
        ])->latest()->get();
    }

    public function getById(int $id): Recipe
    {
        return Recipe::with([
            'product.category',
            'items.ingredient.warehouses',
            'items.ingredient.category',
        ])->findOrFail($id);
    }

    public function create(array $data): Recipe
    {
        return Recipe::create($data);
    }

    public function update(int $id, array $data): Recipe
    {
        $recipe = Recipe::findOrFail($id);
        $recipe->update($data);

        return $recipe;
    }

    public function delete(int $id): void
    {
        Recipe::findOrFail($id)->delete();
    }
}
