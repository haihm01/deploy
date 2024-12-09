<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'min:6', 'max:255']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên',
            'name.max' => 'Tên không được đặt quá 255 kí tự',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            // 'email.unique' => 'Email đã tồn tại',
            'email.max' => 'Email không được đặt quá 255 kí tự',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải lớn hon 6 kí tự',
            'password.max' => 'Mật khẩu không được đặt quá 255 kí tự'
        ];
    }
}
