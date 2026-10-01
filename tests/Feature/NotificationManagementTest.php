<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanReminderTiming;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_notification_routes(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
        $this->post(route('notifications.read', Str::uuid()))
            ->assertRedirect(route('login'));
    }

    public function test_index_displays_only_users_notifications_ten_per_page(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan($user, '本人の通知');
        $otherPlan = $this->createPlan($otherUser, '他人の通知');

        for ($index = 0; $index < 11; $index++) {
            $user->notify(new ReadingPlanReminder(
                $plan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            ));
        }

        $otherUser->notify(new ReadingPlanReminder(
            $otherPlan,
            ReadingPlanReminderTiming::OnDueDate
        ));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('本人の通知')
            ->assertDontSee('他人の通知')
            ->assertViewHas('notifications', function ($notifications): bool {
                return $notifications->perPage() === 10
                    && $notifications->total() === 11;
            });
    }

    public function test_user_can_mark_own_notification_as_read_more_than_once(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, '既読確認用書籍');
        $user->notify(new ReadingPlanReminder(
            $plan,
            ReadingPlanReminderTiming::OnDueDate
        ));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', '通知を既読にしました。');

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', '通知を既読にしました。');
    }

    public function test_user_cannot_read_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = $this->createPlan($owner, '他人の既読確認用書籍');
        $owner->notify(new ReadingPlanReminder(
            $plan,
            ReadingPlanReminderTiming::OnDueDate
        ));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_missing_notification_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('notifications.read', Str::uuid()))
            ->assertNotFound();
    }

    public function test_deleting_plan_also_deletes_its_notifications(): void
    {
        $user = User::factory()->create();
        $plan = $this->createPlan($user, '削除確認用書籍');
        $user->notify(new ReadingPlanReminder(
            $plan,
            ReadingPlanReminderTiming::ThreeDaysBefore
        ));

        $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $plan))
            ->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
        $this->assertDatabaseCount('notifications', 0);
    }

    private function createPlan(User $user, string $title): ReadingPlan
    {
        $book = $this->createBook($user, $title);

        return ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3),
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
