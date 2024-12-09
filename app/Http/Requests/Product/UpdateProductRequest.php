<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|unique:products,slug,' . $this->route('product'),

            'description' => 'nullable|string',
            'images' => 'required|array',
            'category_id' => 'required|exists:categories,id',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'variants' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.image' => 'nullable|string',
            'variants.*.attribute_value_ids' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'images.required' => 'Trường hình ảnh là bắt buộc.',
            'images.array' => 'Hình ảnh phải là một mảng hợp lệ.',
            'category_id.required' => 'ID danh mục là bắt buộc.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'variants.required' => 'Các biến thể là bắt buộc.',
            'variants.*.price.required' => 'Giá là bắt buộc cho mỗi biến thể.',
            'variants.*.price.numeric' => 'Giá phải là một số.',
        ];
    }
}
