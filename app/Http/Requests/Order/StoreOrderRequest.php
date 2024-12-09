<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
        'grand_total' => 'required|numeric|min:0',
        'final_total' => 'required|numeric|min:0',
        'payment_method' => 'required|string|max:255',
        'status' => 'string|in:pending,completed,canceled',
        'notes' => 'string|max:1000',
        'name' => 'required|string|max:255',
        'tel' => 'required|string|max:15',
        'address' => 'required|string|max:500',
        'paid_at' => 'nullable|date',
        'completed_at' => 'nullable|date',
        'code' => 'string|max:50|unique:orders,code',
        ];
    }

    public function messages()
    {
        return [
        'payment_method.required' => 'Vui lòng chọn phương thức thanh toán',
        'payment_method.string' => 'Phương thức thanh toán phải là một chuỗi.',
        'payment_method.max' => 'Phương thức thanh toán không được vượt quá 255 ký tự.',

        'notes.nullable' => 'Ghi chú có thể để trống.',
        'notes.string' => 'Ghi chú phải là một chuỗi.',
        'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',

        'name.required' => 'Tên không được để trống.',
        'name.string' => 'Tên phải là một chuỗi.',
        'name.max' => 'Tên không được vượt quá 255 ký tự.',

        'tel.required' => 'Số điện thoại không được để trống.',
        'tel.string' => 'Số điện thoại phải là một chuỗi.',
        'tel.max' => 'Số điện thoại không được vượt quá 15 ký tự.',

        'address.required' => 'Địa chỉ không được để trống.',
        'address.string' => 'Địa chỉ phải là một chuỗi.',
        'address.max' => 'Địa chỉ không được vượt quá 500 ký tự.',

        'paid_at.date' => 'Ngày thanh toán phải là một ngày hợp lệ.',

        'completed_at.date' => 'Ngày hoàn thành phải là một ngày hợp lệ.',

        'code.string' => 'Mã phải là một chuỗi.',
        'code.max' => 'Mã không được vượt quá 50 ký tự.',
        'code.unique' => 'Mã đã tồn tại trong hệ thống.',
        ];
    }
}