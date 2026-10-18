<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Kasir selalu dipaksa ke outlet miliknya, jadi merchant_id tidak wajib
        // dikirim dari klien. Keeper tetap wajib mengirim merchant_id.
        $isKasirOnly = optional($this->user())->hasRole('kasir')
            && !optional($this->user())->hasRole('keeper');

        return [
            'merchant_id' => ($isKasirOnly ? 'nullable' : 'required') . '|exists:merchants,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ];
    }
}
