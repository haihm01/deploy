<?php

namespace App\Http\Requests\Banner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
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
            'image' => 'nullable|max:255',
            'is_active' => 'boolean',
        ];
    }
    // |url
    public function messages()
    {
        return [
            // 'image.url' => 'Định dạng hình ảnh không hợp lệ.',
            'image.max' => 'Đường dẫn hình ảnh không được vượt quá 255 ký tự.',
        ];
    }
}
