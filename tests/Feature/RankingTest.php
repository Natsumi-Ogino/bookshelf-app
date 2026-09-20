<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_ranking_and_books_without_reviews_are_excluded(): void
    {
        $owner = User::factory()->create();

        $this->createBook($owner, 1);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewIs('ranking.index')
            ->assertViewHas('rankedBooks', fn ($rankedBooks): bool => $rankedBooks->isEmpty())
            ->assertSee('まだレビューが投稿された書籍がありません。');
    }

    public function test_books_are_ranked_by_average_rating_review_count_and_book_id(): void
    {
        $owner = User::factory()->create();
        $reviewerOne = User::factory()->create();
        $reviewerTwo = User::factory()->create();

        $highestRatedBook = $this->createBook($owner, 1);
        $moreReviewedBook = $this->createBook($owner, 2);
        $lowerIdBook = $this->createBook($owner, 3);
        $higherIdBook = $this->createBook($owner, 4);
        $lowerRatedBook = $this->createBook($owner, 5);
        $bookWithoutReviews = $this->createBook($owner, 6);

        $this->createReview($reviewerOne, $highestRatedBook, 5);
        $this->createReview($reviewerOne, $moreReviewedBook, 5);
        $this->createReview($reviewerTwo, $moreReviewedBook, 3);
        $this->createReview($reviewerOne, $lowerIdBook, 4);
        $this->createReview($reviewerOne, $higherIdBook, 4);
        $this->createReview($reviewerOne, $lowerRatedBook, 3);

        $expectedBookIds = [
            $highestRatedBook->id,
            $moreReviewedBook->id,
            $lowerIdBook->id,
            $higherIdBook->id,
            $lowerRatedBook->id,
        ];

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($rankedBooks) use ($expectedBookIds, $bookWithoutReviews): bool {
                return $rankedBooks->pluck('id')->all() === $expectedBookIds
                    && ! $rankedBooks->contains('id', $bookWithoutReviews->id);
            });
    }

    public function test_ranking_displays_only_top_ten_books(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $books = collect();

        for ($sequence = 1; $sequence <= 11; $sequence++) {
            $book = $this->createBook($owner, $sequence);
            $this->createReview($reviewer, $book, 5);
            $books->push($book);
        }

        $expectedBookIds = $books
            ->take(10)
            ->pluck('id')
            ->all();

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertViewHas('rankedBooks', function ($rankedBooks) use ($expectedBookIds): bool {
                return $rankedBooks->count() === 10
                    && $rankedBooks->pluck('id')->all() === $expectedBookIds;
            });
    }

    private function createBook(User $owner, int $sequence): Book
    {
        return $owner->books()->create([
            'title' => "ランキング書籍{$sequence}",
            'author' => 'テスト著者',
            'isbn' => sprintf('978400001%04d', $sequence),
            'published_date' => '2026-09-20',
            'description' => 'ランキングテスト用の書籍です。',
            'image_url' => null,
        ]);
    }

    private function createReview(User $reviewer, Book $book, int $rating): Review
    {
        $review = new Review([
            'rating' => $rating,
            'comment' => 'ランキングテスト用のレビューです。',
        ]);

        $review->user()->associate($reviewer);
        $review->book()->associate($book);
        $review->save();

        return $review;
    }
}
