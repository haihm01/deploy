<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryImportRequest extends FormRequest
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
    public function rules()
    {
        return [
            'variants.*.supplier_id' => 'required|exists:suppliers,id',
            'variants' => 'required|array',
            'variants.*.variant_id' => 'required|exists:variants,id',
            'variants.*.import_price' => 'required|numeric|min:0',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0|lt:variants.*.price',
            'variants.*.end_sale' => 'nullable|date|after:now',
        ];
    }

    public function messages()
    {
        return [
            'variants.required' => 'Danh sách nhập hàng là bắt buộc.',
            'variants.array' => 'Danh sách nhập hàng phải là một mảng.',
            'variants.*.variant_id.required' => 'ID biến thể là bắt buộc.',
            'variants.*.variant_id.exists' => 'Biến thể không tồn tại.',
            'variants.*.supplier_id.required' => 'Nhà cung cấp là bắt buộc.',
            'variants.*.supplier_id.exists' => 'Nhà cung cấp không tồn tại.',
            'variants.*.quantity.required' => 'Số lượng là bắt buộc.',
            'variants.*.quantity.integer' => 'Số lượng phải là số nguyên.',
            'variants.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'variants.*.import_price.required' => 'Giá nhập của biến thể là bắt buộc.',
            'variants.*.import_price.numeric' => 'Giá nhập của biến thể phải là số.',
            'variants.*.import_price.min' => 'Giá nhập của biến thể phải lớn hơn 0.',
            'variants.*.price.numeric' => 'Giá bán phải là số.',
            'variants.*.price.min' => 'Giá bán phải lớn hơn 0.',
            'variants.*.sale_price.numeric' => 'Giá khuyến mãi phải là số.',
            'variants.*.sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá bán.',
            'variants.*.end_sale.date' => 'Ngày kết thúc khuyến mãi không hợp lệ.',
            'variants.*.end_sale.after' => 'Ngày kết thúc khuyến mãi phải sau ngày hiện tại.',
        ];
    }
}
