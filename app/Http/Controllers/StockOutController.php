<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockOutRequest;
use App\Http\Resources\StockOutResource;
use App\Services\StockOutService;
use Illuminate\Support\Facades\Auth;

class StockOutController extends Controller
{
    private StockOutService $stockOutService;

    public function __construct(StockOutService $stockOutService)
    {
        $this->stockOutService = $stockOutService;
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('keeper')) {
            if (!$user->merchant) {
                return response()->json(['message' => 'No merchant assigned'], 403);
            }

            $stockOuts = $this->stockOutService->getByMerchant($user->merchant->id);
            return response()->json(StockOutResource::collection($stockOuts));
        }

        $stockOuts = $this->stockOutService->getAll();
        return response()->json(StockOutResource::collection($stockOuts));
    }

    public function store(StockOutRequest $request)
    {
        $stockOut = $this->stockOutService->createStockOut($request->validated());

        return response()->json([
            'message' => 'Stock out recorded successfully',
            'data' => new StockOutResource($stockOut),
        ], 201);
    }
}
