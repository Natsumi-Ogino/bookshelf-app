<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_review_routes(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner);
        $review = $this->createReview($owner, $book);

        $this->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => '未ログイン投稿',
        ])->assertRedirect(route('login'));

        $this->get(route('reviews.edit', $review))
            ->assertRedirect(route('login'));

        $this->put(route('reviews.update', $review), [
            'rating' => 3,
            'comment' => '未ログイン更新',
        ])->assertRedirect(route('login'));

        $this->delete(route('reviews.destroy', $review))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_authenticated_user_can_post_review(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => 'とても良い書籍でした。',
            ]);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', 'レビューを投稿しました。');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い書籍でした。',
        ]);
    }

    public function test_review_requires_valid_rating_and_comment(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), [
                'rating' => 6,
                'comment' => '',
            ]);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHasErrors([
                'rating' => '評価は1〜5の整数で入力してください。',
                'comment',
            ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_store_review_uses_approved_required_rating_message(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), [
                'comment' => '評価が未入力のレビューです。',
            ])
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHasErrors([
                'rating' => '評価は必須です。',
            ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_update_review_uses_approved_rating_error_messages(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $review = $this->createReview($user, $book);

        $this->actingAs($user)
            ->from(route('reviews.edit', $review))
            ->put(route('reviews.update', $review), [
                'comment' => '評価が未入力の更新です。',
            ])
            ->assertRedirect(route('reviews.edit', $review))
            ->assertSessionHasErrors([
                'rating' => '評価は必須です。',
            ]);

        $this->actingAs($user)
            ->from(route('reviews.edit', $review))
            ->put(route('reviews.update', $review), [
                'rating' => '不正な値',
                'comment' => '評価が整数ではない更新です。',
            ])
            ->assertRedirect(route('reviews.edit', $review))
            ->assertSessionHasErrors([
                'rating' => '評価は1〜5の整数で入力してください。',
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => 'テスト用レビューです。',
        ]);
    }

    public function test_user_cannot_post_second_review_for_same_book(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);

        $this->createReview($user, $book);

        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 3,
                'comment' => '2件目のレビューです。',
            ]);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHasErrors([
                'review' => 'この書籍には既にレビューを投稿しています。',
            ]);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_review_owner_can_edit_update_and_delete_review(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $review = $this->createReview($user, $book);

        $this->actingAs($user)
            ->get(route('reviews.edit', $review))
            ->assertOk()
            ->assertViewIs('reviews.edit')
            ->assertViewHas('review', $review);

        $this->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 2,
                'comment' => '更新後のレビューです。',
            ])
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', 'レビューを更新しました。');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 2,
            'comment' => '更新後のレビューです。',
        ]);

        $this->actingAs($user)
            ->delete(route('reviews.destroy', $review))
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', 'レビューを削除しました。');

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_non_owner_cannot_edit_update_or_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($owner);
        $review = $this->createReview($owner, $book);

        $this->actingAs($otherUser)
            ->get(route('reviews.edit', $review))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('reviews.update', $review), [
                'rating' => 1,
                'comment' => '変更を試みました。',
            ])
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => 'テスト用レビューです。',
        ]);
    }

    private function createBook(User $user): Book
    {
        return $user->books()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000000',
            'published_date' => '2026-09-18',
            'description' => 'テスト用の書籍です。',
            'image_url' => null,
        ]);
    }

    private function createReview(User $user, Book $book): Review
    {
        $review = new Review([
            'rating' => 4,
            'comment' => 'テスト用レビューです。',
        ]);

        $review->user()->associate($user);
        $review->book()->associate($book);
        $review->save();

        return $review;
    }
}
