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
        return [
            'user_id' => ['bail', 'required', 'integer', 'exists:users,id'],
        ] + parent::rules();
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
