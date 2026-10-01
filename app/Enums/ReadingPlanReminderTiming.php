<?php

namespace App\Enums;

enum ReadingPlanReminderTiming: string
{
    case ThreeDaysBefore = 'three_days_before';
    case OnDueDate = 'on_due_date';
    case ThreeDaysAfter = 'three_days_after';

    public function title(): string
    {
        return match ($this) {
            self::ThreeDaysBefore => '読書期限が近づいています',
            self::OnDueDate => '今日は読書目標日です',
            self::ThreeDaysAfter => '読書期限を過ぎています',
        };
    }

    public function body(string $bookTitle): string
    {
        return match ($this) {
            self::ThreeDaysBefore => "『{$bookTitle}』の目標日まであと3日です。",
            self::OnDueDate => "『{$bookTitle}』の読書目標日は今日です。",
            self::ThreeDaysAfter => "『{$bookTitle}』の読書目標日から3日が経過しました。",
        };
    }
}
