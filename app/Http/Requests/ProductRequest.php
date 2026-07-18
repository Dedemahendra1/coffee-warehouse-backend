<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255|unique:products,name,' . $this->route('product'),

            'thumbnail' => $this->isMethod('post')
                ? 'required|image|mimes:jpeg,png,jpg|max:2048'
                : 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            'about' => 'required|string',

            // Tambahan
            'unit' => 'required|string|max:20',

            'price' => 'required|integer|min:0',

            'category_id' => 'required|exists:categories,id',

            'is_popular' => 'boolean',
        ];
    }
}