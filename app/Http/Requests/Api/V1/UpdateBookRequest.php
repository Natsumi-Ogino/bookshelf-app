<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\UpdateBookRequest as WebUpdateBookRequest;
use App\Models\Book;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends WebUpdateBookRequest
{
    private ?Book $bookForValidation = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $bookId = filter_var($this->route('book'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        $this->bookForValidation = $bookId === false
            ? null
            : Book::query()->find($bookId);

        if ($this->bookForValidation === null) {
            throw new HttpResponseException(response()->json([
                'error' => '書籍が見つかりませんでした。',
            ], 404));
        }
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $uniqueIsbn = Rule::unique('books', 'isbn')
            ->ignore($this->bookForValidation);

        $rules['isbn'] = [
            'required',
            'string',
            'regex:/\A[0-9]{13}\z/',
            $uniqueIsbn,
        ];

        return [
            'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
        ] + $rules;
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '登録者IDは必須です。',
            'user_id.integer' => '指定された登録者は存在しません。',
            'user_id.exists' => '指定された登録者は存在しません。',
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
