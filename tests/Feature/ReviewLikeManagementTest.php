<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_like_review(): void
    {
        $reviewOwner = User::factory()->create();
        $book = $this->createBook($reviewOwner);
        $review = $this->createReview($reviewOwner, $book);

        $this->post(route('reviews.like', $review))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_authenticated_user_can_add_remove_and_readd_review_like(): void
    {
        $reviewOwner = User::factory()->create();
        $user = User::factory()->create();
        $book = $this->createBook($reviewOwner);
        $review = $this->createReview($reviewOwner, $book);
        $bookUrl = route('books.show', $book);

        $this->actingAs($user)
            ->post(route('reviews.like', $review))
            ->assertRedirect($bookUrl)
            ->assertSessionHas('success', 'レビューにいいねしました。');

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this->actingAs($user)
            ->post(route('reviews.like', $review))
            ->assertRedirect($bookUrl)
            ->assertSessionHas('success', 'レビューのいいねを解除しました。');

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this->actingAs($user)
            ->post(route('reviews.like', $review))
            ->assertRedirect($bookUrl)
            ->assertSessionHas('success', 'レビューにいいねしました。');

        $this->assertDatabaseCount('review_likes', 1);
    }

    public function test_user_cannot_like_own_review(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $review = $this->createReview($user, $book);

        $this->actingAs($user)
            ->post(route('reviews.like', $review))
            ->assertForbidden();

        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_book_detail_hides_like_button_for_own_review(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = $this->createBook($user);
        $ownReview = $this->createReview($user, $book);
        $otherReview = $this->createReview($otherUser, $book);

        $this->actingAs($user)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee(route('reviews.like', $ownReview), false)
            ->assertSee(route('reviews.like', $otherReview), false);
    }

    private function createBook(User $user): Book
    {
        return $user->books()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000000',
            'published_date' => '2026-09-19',
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
