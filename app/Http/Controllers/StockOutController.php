<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockOutRequest;
use App\Http\Resources\StockOutResource;
use App\Services\StockOutService;
use App\Support\OutletAccess;
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

        // keeper & kasir hanya melihat outlet miliknya; manager melihat semua
        if ($user->hasRole('keeper') || $user->hasRole('kasir')) {
            $merchantId = OutletAccess::accessibleMerchantId($user);

            if (!$merchantId) {
                return response()->json(['message' => 'No merchant assigned'], 403);
            }

            $stockOuts = $this->stockOutService->getByMerchant($merchantId);
            return response()->json(StockOutResource::collection($stockOuts));
        }

        $stockOuts = $this->stockOutService->getAll();
        return response()->json(StockOutResource::collection($stockOuts));
    }

    public function store(StockOutRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        // Kasir tidak boleh menulis ke outlet lain: merchant_id dipaksa ke
        // outlet miliknya (users.merchant_id).
        if ($user->hasRole('kasir') && !$user->hasRole('keeper')) {
            $ownMerchantId = OutletAccess::accessibleMerchantId($user);

            if (!$ownMerchantId) {
                return response()->json(['message' => 'No merchant assigned'], 403);
            }

            if (isset($data['merchant_id']) && (int) $data['merchant_id'] !== $ownMerchantId) {
                return response()->json([
                    'message' => 'Unauthorized: You can only manage your own outlet.',
                ], 403);
            }

            $data['merchant_id'] = $ownMerchantId;
        }

        $stockOut = $this->stockOutService->createStockOut($data);

        return response()->json([
            'message' => 'Stock out recorded successfully',
            'data' => new StockOutResource($stockOut),
        ], 201);
    }
}
