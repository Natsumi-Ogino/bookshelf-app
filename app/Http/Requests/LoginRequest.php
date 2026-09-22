<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class LoginRequest extends FortifyLoginRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['email'] = 'required|string|email';

        return $rules;
    }
}
