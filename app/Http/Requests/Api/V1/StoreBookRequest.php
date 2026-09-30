<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\StoreBookRequest as WebStoreBookRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBookRequest extends WebStoreBookRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['isbn'] = [
            'required',
            'string',
            'regex:/\A[0-9]{13}\z/',
            'unique:books,isbn',
        ];
        $rules['published_date'] = [
            'required',
            'date',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'isbn.required' => 'ISBNは必須です。',
            'published_date.required' => '出版日は必須です。',
        ] + parent::messages();
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
