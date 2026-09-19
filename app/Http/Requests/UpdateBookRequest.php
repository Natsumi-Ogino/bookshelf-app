<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'author' => [
                'required',
                'string',
                'max:255',
            ],
            'isbn' => [
                'required',
                'string',
                'regex:/\A[0-9]{13}\z/',
                Rule::unique('books', 'isbn')
                    ->ignore($this->route('book')),
            ],
            'published_date' => [
                'required',
                'date',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'image_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
            'genres' => [
                'required',
                'array',
                'min:1',
            ],
            'genres.*' => [
                'integer',
                'distinct',
                'exists:genres,id',
            ],
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者名は必須です。',
            'author.max' => '著者名は255文字以内で入力してください。',
            'isbn.required' => 'ISBNは必須です。',
            'isbn.regex' => 'ISBNは13桁で入力してください。',
            'isbn.unique' => 'このISBNは既に登録されています。',
            'published_date.required' => '出版日は必須です。',
            'published_date.date' => '出版日は有効な日付形式で入力してください。',
            'image_url.url' => '画像URLは有効なURL形式で入力してください。',
            'image_url.max' => '画像URLは2048文字以内で入力してください。',
            'genres.required' => 'ジャンルは1つ以上選択してください。',
            'genres.array' => 'ジャンルは1つ以上選択してください。',
            'genres.min' => 'ジャンルは1つ以上選択してください。',
            'genres.*.integer' => '選択されたジャンルは存在しません。',
            'genres.*.distinct' => '同じジャンルを重複して選択できません。',
            'genres.*.exists' => '選択されたジャンルは存在しません。',
        ];
    }
}
