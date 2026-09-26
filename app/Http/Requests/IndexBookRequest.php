<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $keyword = $this->input('keyword');

        if (is_string($keyword)) {
            $keyword = trim($keyword);

            $this->merge([
                'keyword' => $keyword === '' ? null : $keyword,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'keyword' => ['bail', 'nullable', 'string', 'max:255'],
            'genre' => ['bail', 'nullable', 'integer', 'exists:genres,id'],
            'sort' => ['bail', 'nullable', 'string', 'in:latest,oldest,title,rating'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',
            'genre.integer' => '選択されたジャンルが存在しません。',
            'genre.exists' => '選択されたジャンルが存在しません。',
            'sort.string' => '並び順の指定が正しくありません。',
            'sort.in' => '並び順の指定が正しくありません。',
        ];
    }
}
