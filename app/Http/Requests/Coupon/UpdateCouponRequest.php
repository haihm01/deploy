<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
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
            'code' => 'required|max:255|unique:coupons,code,' . $this->route('coupon'),
            'discount' => 'required',
            'discount_type' => 'required',
            'usage_limit' => 'required',
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            'status' => 'boolean',
            'is_featured' => 'boolean',
            'min_order_value' => 'nullable|min:0|numeric',
            'max_order_value'   => 'nullable|numeric|gt:min_order_value'
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Yêu cầu nhập',
            'code.max' => 'Không nhập quá 255 ký tự',
            'code.unique' => 'Mã này đã tồn tại',
            'discount.required' => 'Yêu cầu nhập',
            'discount_type.required' => 'Yêu cầu nhập',
            'usage_limit.required' => 'Yêu cầu nhập',
            'min_order_value.min' => 'Giá trị phải là số ',
            'min_order_value.numeric' => 'Vui lòng nhập số',
            'max_order_value.numeric' => 'Vui lòng nhập số',
            'max_order_value.gt:min_order_value' => 'Giá trị phải lớn hơn giá trị đơn hàng thấp nhất',
            'status.in' => 'Trạng thái đã chọn không hợp lệ',
            'is_featured.in' => 'Trạng thái đã chọn không hợp lệ',
            'end_date.date' => 'Ngày kết thúc phải là một ngày hợp lệ.',
            'end_date.date_format' => 'Ngày kết thúc phải có định dạng YYYY-MM-DD.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'start_date.date' => 'Ngày bắt đầu phải là một ngày hợp lệ.',
            'start_date.date_format' => 'Ngày bắt đầu phải có định dạng YYYY-MM-DD.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        $response = response()->json([
            'errors' => $errors->messages()
        ], Response::HTTP_BAD_REQUEST);

        throw new HttpResponseException($response);
    }
}
