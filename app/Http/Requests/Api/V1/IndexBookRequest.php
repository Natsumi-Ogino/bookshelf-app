<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IndexBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['bail', 'nullable', 'string', 'max:255'],
            'genre' => ['bail', 'nullable', 'integer', 'exists:genres,id'],
            'page' => ['bail', 'nullable', 'integer', 'min:1'],
            'per_page' => ['bail', 'nullable', 'integer', 'between:1,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',
            'genre.integer' => '選択されたジャンルが存在しません。',
            'genre.exists' => '選択されたジャンルが存在しません。',
            'page.integer' => 'ページ番号は1以上の整数で指定してください。',
            'page.min' => 'ページ番号は1以上の整数で指定してください。',
            'per_page.integer' => '1ページ当たりの件数は1から100の整数で指定してください。',
            'per_page.between' => '1ページ当たりの件数は1から100の整数で指定してください。',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
