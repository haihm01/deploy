<?php

namespace App\Http\Requests\Attribute;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class StoreAttributeRequest extends FormRequest
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
            'name' => 'required|max:255',
            'slug' => 'required|string|max:255|unique:attributes',
            'type' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Vui lòng nhập',
            'name.max'  => 'Tối đa 255 ký tự',
            'type.required' => 'Vui lòng chọn',
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