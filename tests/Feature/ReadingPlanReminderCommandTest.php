<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanReminderTiming;
use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReadingPlanReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_expires_plans_and_sends_three_approved_reminders(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 00:30:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $threeDaysBefore = $this->createPlan(
            $user,
            '3日前対象',
            '2026-10-04',
            ReadingPlanStatus::InProgress
        );
        $onDueDate = $this->createPlan(
            $user,
            '当日対象',
            '2026-10-01',
            ReadingPlanStatus::InProgress
        );
        $threeDaysAfter = $this->createPlan(
            $user,
            '3日後対象',
            '2026-09-28',
            ReadingPlanStatus::InProgress
        );
        $this->createPlan(
            $user,
            '対象外',
            '2026-10-08',
            ReadingPlanStatus::InProgress
        );
        $this->createPlan(
            $user,
            '完了済み',
            '2026-10-01',
            ReadingPlanStatus::Completed
        );

        Notification::fake();

        $this->artisan('reading-plans:process-reminders')
            ->assertSuccessful();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $threeDaysAfter->id,
            'status' => ReadingPlanStatus::Expired->value,
            'completed_at' => null,
        ]);

        $expected = [
            $threeDaysBefore->id => ReadingPlanReminderTiming::ThreeDaysBefore,
            $onDueDate->id => ReadingPlanReminderTiming::OnDueDate,
            $threeDaysAfter->id => ReadingPlanReminderTiming::ThreeDaysAfter,
        ];

        Notification::assertSentToTimes($user, ReadingPlanReminder::class, 3);
        foreach ($expected as $readingPlanId => $timing) {
            Notification::assertSentTo(
                $user,
                function (ReadingPlanReminder $notification) use (
                    $user,
                    $readingPlanId,
                    $timing
                ): bool {
                    $data = $notification->toDatabase($user);

                    return $data['reading_plan_id'] === $readingPlanId
                        && $data['timing'] === $timing->value;
                }
            );
        }
    }

    public function test_command_does_not_send_past_or_out_of_range_reminders(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 00:30:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $this->createPlan(
            $user,
            '2日前登録',
            '2026-10-03',
            ReadingPlanStatus::InProgress
        );
        $this->createPlan(
            $user,
            '2日超過',
            '2026-09-29',
            ReadingPlanStatus::Expired
        );

        Notification::fake();

        $this->artisan('reading-plans:process-reminders')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_does_not_create_duplicate_notifications(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 00:30:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $this->createPlan(
            $user,
            '重複防止対象',
            '2026-10-04',
            ReadingPlanStatus::InProgress
        );

        $this->artisan('reading-plans:process-reminders')
            ->assertSuccessful();
        $this->artisan('reading-plans:process-reminders')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);
    }

    private function createPlan(
        User $user,
        string $title,
        string $targetDate,
        ReadingPlanStatus $status
    ): ReadingPlan {
        $book = $this->createBook($user, $title);

        return ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => $status,
            'completed_at' => $status === ReadingPlanStatus::Completed ? now() : null,
        ]);
    }

    private function createBook(User $owner, string $title): Book
    {
        return $owner->books()->create([
            'title' => $title,
            'author' => 'テスト著者',
            'description' => 'テスト説明',
        ]);
    }
}
