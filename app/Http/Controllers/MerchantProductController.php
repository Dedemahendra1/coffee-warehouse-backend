<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerchantProductRequest;
use App\Http\Requests\MerchantProductUpdateRequest;
use App\Services\MerchantProductService;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MerchantProductController extends Controller
{

    private MerchantProductService $merchantProductService;
    private MerchantRepository $merchantRepository;

    public function __construct(MerchantProductService $merchantProductService, MerchantRepository $merchantRepository)
    {
        $this->merchantProductService = $merchantProductService;
        $this->merchantRepository = $merchantRepository;
    }

    private function authorizeKeeper(int $merchantId): void
    {
        if (Auth::user()->hasRole('keeper')) {
            $merchant = $this->merchantRepository->getById($merchantId, ['id', 'keeper_id']);
            if (!$merchant || Auth::id() !== $merchant->keeper_id) {
                throw ValidationException::withMessages([
                    'authorization' => ['Unauthorized: You can only manage your own outlet.']
                ]);
            }
        }
    }

    public function store(MerchantProductRequest $request, int $merchant)
    {
        $this->authorizeKeeper($merchant);

        $validated = $request->validated();

        $validated['merchant_id'] = $merchant;

        $merchantProduct = $this->merchantProductService->assignProductToMerchant($validated);

        return response()->json([
            'message' => 'Product assigned to merchant successfully',
            'data' => $merchantProduct,
        ], 201);
    }

    public function update(MerchantProductUpdateRequest $request, int $merchantId, int $productId)
    {
        $this->authorizeKeeper($merchantId);

        $validated = $request->validated();

        $merchantProduct = $this->merchantProductService->updateStock(
            $merchantId,
            $productId,
            $validated['stock'],
            $validated['warehouse_id']
        );

        return response()->json([
            'message' => 'Stock updated successfully',
            'data' => $merchantProduct,
        ]);
    }

    public function destroy(int $merchant, int $product)
    {
        $this->authorizeKeeper($merchant);

        $this->merchantProductService->removeProductFromMerchant($merchant, $product);

        return response()->json([
            'message' => 'Product detached from merchant successfully',
        ]);
    }

}
