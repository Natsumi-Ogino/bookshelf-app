<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanReminderTiming;
use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessReadingPlanReminders extends Command
{
    protected $signature = 'reading-plans:process-reminders';

    protected $description = '読書計画の期限切れ更新とリマインダー通知を実行します';

    public function handle(): int
    {
        $today = CarbonImmutable::today('Asia/Tokyo');

        ReadingPlan::query()
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', $today)
            ->update([
                'status' => ReadingPlanStatus::Expired,
                'completed_at' => null,
            ]);

        $reminderConditions = [
            [
                'timing' => ReadingPlanReminderTiming::ThreeDaysBefore,
                'status' => ReadingPlanStatus::InProgress,
                'target_date' => $today->addDays(3),
            ],
            [
                'timing' => ReadingPlanReminderTiming::OnDueDate,
                'status' => ReadingPlanStatus::InProgress,
                'target_date' => $today,
            ],
            [
                'timing' => ReadingPlanReminderTiming::ThreeDaysAfter,
                'status' => ReadingPlanStatus::Expired,
                'target_date' => $today->subDays(3),
            ],
        ];

        foreach ($reminderConditions as $condition) {
            ReadingPlan::query()
                ->where('status', $condition['status'])
                ->whereDate('target_date', $condition['target_date'])
                ->orderBy('id')
                ->chunkById(100, function ($readingPlans) use ($condition): void {
                    foreach ($readingPlans as $readingPlan) {
                        $this->sendReminderOnce(
                            $readingPlan,
                            $condition['timing'],
                            $condition['status'],
                            $condition['target_date']
                        );
                    }
                });
        }

        return self::SUCCESS;
    }

    private function sendReminderOnce(
        ReadingPlan $readingPlan,
        ReadingPlanReminderTiming $timing,
        ReadingPlanStatus $expectedStatus,
        CarbonImmutable $expectedTargetDate
    ): void {
        DB::transaction(function () use (
            $readingPlan,
            $timing,
            $expectedStatus,
            $expectedTargetDate
        ): void {
            $lockedPlan = ReadingPlan::query()
                ->with(['book', 'user'])
                ->lockForUpdate()
                ->find($readingPlan->id);

            if (
                $lockedPlan === null
                || $lockedPlan->status !== $expectedStatus
                || ! $lockedPlan->target_date->isSameDay($expectedTargetDate)
            ) {
                return;
            }

            $alreadySent = $lockedPlan->user
                ->notifications()
                ->where('type', ReadingPlanReminder::class)
                ->where('data->reading_plan_id', $lockedPlan->id)
                ->where('data->timing', $timing->value)
                ->exists();

            if ($alreadySent) {
                return;
            }

            $lockedPlan->user->notify(
                new ReadingPlanReminder($lockedPlan, $timing)
            );
        });
    }
}
