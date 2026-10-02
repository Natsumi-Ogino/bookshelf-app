<?php

namespace App\Http\Requests;

use App\Rules\ValidIsbn13;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LookupBookByIsbnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'isbn' => $this->route('isbn'),
        ]);
    }

    public function rules(): array
    {
        return [
            'isbn' => [
                'bail',
                'required',
                'string',
                new ValidIsbn13,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'isbn.required' => 'ISBNは13桁で入力してください。',
            'isbn.string' => 'ISBNは13桁で入力してください。',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => $validator->errors()->first('isbn'),
            ], 400)
        );
    }
}
