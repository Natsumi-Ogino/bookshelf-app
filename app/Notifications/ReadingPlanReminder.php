<?php

namespace App\Notifications;

use App\Enums\ReadingPlanReminderTiming;
use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ReadingPlan $readingPlan,
        private readonly ReadingPlanReminderTiming $timing
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, int|string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
            'timing' => $this->timing->value,
            'title' => $this->timing->title(),
            'body' => $this->timing->body($this->readingPlan->book->title),
        ];
    }
}
