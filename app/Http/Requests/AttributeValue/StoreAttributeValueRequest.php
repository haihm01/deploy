<?php

namespace App\Http\Requests\AttributeValue;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class StoreAttributeValueRequest extends FormRequest
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
           'value' => 'required|unique:attribute_values',
                'attribute_id' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'value.unique' => 'Giá trị thuộc tính đã tồn tại',
            'value.required' => 'Vui lòng nhập giá trị thuộc tính',
            'attribute_id.required' => 'Vui lòng nhập chọn thuộc tính',
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