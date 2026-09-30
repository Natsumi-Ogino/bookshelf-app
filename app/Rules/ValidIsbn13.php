<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIsbn13 implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        if (
            ! is_string($value)
            || preg_match('/\A[0-9]{13}\z/', $value) !== 1
        ) {
            $fail('ISBNは13桁で入力してください。');

            return;
        }

        $sum = 0;

        for ($index = 0; $index < 12; $index++) {
            $weight = $index % 2 === 0 ? 1 : 3;
            $sum += (int) $value[$index] * $weight;
        }

        $expectedCheckDigit = (10 - ($sum % 10)) % 10;
        $actualCheckDigit = (int) $value[12];

        if ($actualCheckDigit !== $expectedCheckDigit) {
            $fail('ISBNは13桁で入力してください。');
        }
    }
}
