<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_reading_plan_routes(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner, '認証確認用書籍');
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $this->get(route('reading-plans.index'))
            ->assertRedirect(route('login'));
        $this->get(route('reading-plans.create'))
            ->assertRedirect(route('login'));
        $this->post(route('reading-plans.store'))
            ->assertRedirect(route('login'));
        $this->get(route('reading-plans.edit', $readingPlan))
            ->assertRedirect(route('login'));
        $this->put(route('reading-plans.update', $readingPlan))
            ->assertRedirect(route('login'));
        $this->patch(route('reading-plans.complete', $readingPlan))
            ->assertRedirect(route('login'));
        $this->delete(route('reading-plans.destroy', $readingPlan))
            ->assertRedirect(route('login'));
    }

    public function test_index_displays_only_authenticated_users_plans_in_approved_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $laterBook = $this->createBook($user, '後の計画');
        $earlierBook = $this->createBook($user, '先の計画');
        $otherBook = $this->createBook($otherUser, '他人の計画');

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $laterBook->id,
            'target_date' => today()->addDays(10),
        ]);
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $earlierBook->id,
            'target_date' => today()->addDays(2),
        ]);
        ReadingPlan::factory()->completed()->create([
            'user_id' => $user->id,
            'book_id' => $this->createBook($user, '完了した計画')->id,
        ]);
        ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
        ]);

        $this->actingAs($user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertSeeInOrder(['先の計画', '後の計画'])
            ->assertDontSee('他人の計画');

        $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::Completed->value,
            ]))
            ->assertOk()
            ->assertSee('完了した計画')
            ->assertDontSee('先の計画');
    }

    public function test_invalid_status_filter_returns_approved_japanese_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reading-plans.index', ['status' => 'invalid']))
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHasErrors([
                'status' => '選択された状態が正しくありません。',
            ]);
    }

    public function test_user_can_create_plan_without_overriding_owner_or_status(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 10:00:00'));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($otherUser, '計画対象書籍');

        $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-10-05',
                'user_id' => $otherUser->id,
                'status' => ReadingPlanStatus::Completed->value,
                'completed_at' => now(),
            ])
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を作成しました。');

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-10-05',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_validation_and_duplicate_plan_return_approved_messages(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 10:00:00'));

        $user = User::factory()->create();
        $book = $this->createBook($user, '重複確認用書籍');

        $this->actingAs($user)
            ->post(route('reading-plans.store'), [])
            ->assertSessionHasErrors([
                'book_id' => '書籍を選択してください。',
                'target_date' => '期日は必須です。',
            ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-10-05',
        ]);

        $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-10-06',
            ])
            ->assertSessionHasErrors([
                'book_id' => 'この書籍は既に進行中の読書計画が存在します。',
            ]);

        $this->assertDatabaseCount('reading_plans', 1);
    }

    public function test_expired_plan_can_be_rescheduled_and_reactivated(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 10:00:00'));

        $user = User::factory()->create();
        $book = $this->createBook($user, '再開用書籍');
        $readingPlan = ReadingPlan::factory()->expired()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-09-28',
        ]);

        $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan))
            ->assertOk()
            ->assertSee('再開用書籍');

        $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => '2026-10-10',
            ])
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を更新しました。');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => '2026-10-10',
            'status' => ReadingPlanStatus::InProgress->value,
            'completed_at' => null,
        ]);
    }

    public function test_completed_plan_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user, '完了済み書籍');
        $readingPlan = ReadingPlan::factory()->completed()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today(),
        ]);

        $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan))
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('error', '完了済みの読書計画は編集できません。');

        $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => today()->addWeek()->toDateString(),
            ])
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('error', '完了済みの読書計画は編集できません。');
    }

    public function test_owner_can_complete_and_delete_plan(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 10:00:00'));

        $user = User::factory()->create();
        $book = $this->createBook($user, '読了対象書籍');
        $readingPlan = ReadingPlan::factory()->expired()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user)
            ->patch(route('reading-plans.complete', $readingPlan))
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を完了しました。');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
            'completed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan))
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を削除しました。');

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_non_owner_cannot_edit_update_complete_or_delete_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner, '所有者確認用書籍');
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($otherUser)
            ->get(route('reading-plans.edit', $readingPlan))
            ->assertForbidden();
        $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => today()->addWeek()->toDateString(),
            ])
            ->assertForbidden();
        $this->actingAs($otherUser)
            ->patch(route('reading-plans.complete', $readingPlan))
            ->assertForbidden();
        $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $readingPlan))
            ->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::InProgress->value,
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
