<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateRecipeRequest;
use App\Http\Requests\RecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Services\RecipeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RecipeController extends Controller
{
    private RecipeService $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    public function index()
    {
        $recipes = $this->recipeService->getAll();

        return response()->json(RecipeResource::collection($recipes));
    }

    public function show(int $id)
    {
        try {
            $recipe = $this->recipeService->getById($id);

            return response()->json(new RecipeResource($recipe));
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'recipe not found',
            ], 404);
        }
    }

    public function store(RecipeRequest $request)
    {
        $recipe = $this->recipeService->create($request->validated());

        return response()->json(new RecipeResource($recipe), 201);
    }

    public function update(RecipeRequest $request, int $id)
    {
        try {
            $recipe = $this->recipeService->update($id, $request->validated());

            return response()->json(new RecipeResource($recipe));
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'recipe not found',
            ], 404);
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->recipeService->delete($id);

            return response()->json([
                'message' => 'recipe deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'recipe not found',
            ], 404);
        }
    }

    public function calculate(CalculateRecipeRequest $request, int $id)
    {
        try {
            $result = $this->recipeService->calculate($id, (float) $request->validated('quantity'));

            return response()->json($result);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'recipe not found',
            ], 404);
        }
    }
}
