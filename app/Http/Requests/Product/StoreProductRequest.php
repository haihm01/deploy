<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'slug' => 'required|string|unique:products,slug',
            'description' => 'nullable|string',
            'images' => 'required|array',
            'category_id' => 'required|exists:categories,id',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'variants' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.attribute_values' => 'required|array',
            'variants.*.image' => 'nullable|string',
            'variants.*.attribute_values.*' => 'integer|exists:attribute_values,id',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'slug.required' => 'Slug là bắt buộc.',
            'slug.unique' => 'Slug đã tồn tại, vui lòng chọn slug khác.',
            'images.required' => 'Trường hình ảnh là bắt buộc.',
            'images.array' => 'Hình ảnh phải là một mảng hợp lệ.',
            'category_id.required' => 'ID danh mục là bắt buộc.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'variants.required' => 'Biến thể là bắt buộc.',
            'variants.array' => 'Biến thể phải là một mảng.',

            'variants.*.price.required' => 'Giá là bắt buộc cho từng biến thể.',
            'variants.*.price.numeric' => 'Giá phải là một số.',
            'variants.*.price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
            'variants.*.sale_price.numeric' => 'Giá bán phải là một số.',
            'variants.*.sale_price.min' => 'Giá bán phải lớn hơn hoặc bằng 0.',
            'variants.*.attribute_values.required' => 'Giá trị thuộc tính là bắt buộc cho từng biến thể.',
            'variants.*.attribute_values.array' => 'Giá trị thuộc tính phải là một mảng.',
            'variants.*.attribute_values.*.integer' => 'Giá trị thuộc tính phải là một số nguyên.',
            'variants.*.attribute_values.*.exists' => 'Giá trị thuộc tính không tồn tại.',
        ];
    }
}
