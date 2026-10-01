<?php

namespace App\Http\Requests;

use App\Models\ReadingPlan;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $readingPlan = $this->route('reading_plan');

        return $readingPlan instanceof ReadingPlan
            && $this->user()?->can('update', $readingPlan) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $today = CarbonImmutable::today('Asia/Tokyo')->toDateString();

        return [
            'target_date' => ['required', 'date', "after_or_equal:{$today}"],
        ];
    }

    public function messages(): array
    {
        return [
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください。',
        ];
    }
}
