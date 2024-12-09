<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8',
            'token' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'email.exists' => 'Email không tồn tại trong hệ thống.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'email.required' => 'Email là bắt buộc.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'token.required' => 'Token là bắt buộc.',
        ];
    }
}
