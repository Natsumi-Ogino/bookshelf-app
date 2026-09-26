<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_and_latest_sort_use_created_at_descending_and_id_ascending(): void
    {
        $owner = User::factory()->create();

        $oldestBook = $this->createBook(
            $owner,
            1,
            '古い書籍',
            '2026-09-01 00:00:00'
        );
        $firstLatestBook = $this->createBook(
            $owner,
            2,
            '新しい書籍A',
            '2026-09-03 00:00:00'
        );
        $secondLatestBook = $this->createBook(
            $owner,
            3,
            '新しい書籍B',
            '2026-09-03 00:00:00'
        );

        $expectedIds = [
            $firstLatestBook->id,
            $secondLatestBook->id,
            $oldestBook->id,
        ];

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewHas(
                'books',
                fn ($books): bool => $books->pluck('id')->all() === $expectedIds
            );

        $this->get(route('books.index', ['sort' => 'latest']))
            ->assertOk()
            ->assertViewHas(
                'books',
                fn ($books): bool => $books->pluck('id')->all() === $expectedIds
            );
    }

    public function test_oldest_sort_uses_created_at_and_id_ascending(): void
    {
        $owner = User::factory()->create();

        $firstOldestBook = $this->createBook(
            $owner,
            1,
            '古い書籍A',
            '2026-09-01 00:00:00'
        );
        $secondOldestBook = $this->createBook(
            $owner,
            2,
            '古い書籍B',
            '2026-09-01 00:00:00'
        );
        $latestBook = $this->createBook(
            $owner,
            3,
            '新しい書籍',
            '2026-09-03 00:00:00'
        );

        $this->get(route('books.index', ['sort' => 'oldest']))
            ->assertOk()
            ->assertViewHas('books', function ($books) use (
                $firstOldestBook,
                $secondOldestBook,
                $latestBook
            ): bool {
                return $books->pluck('id')->all() === [
                    $firstOldestBook->id,
                    $secondOldestBook->id,
                    $latestBook->id,
                ];
            });
    }

    public function test_title_sort_uses_title_and_id_ascending(): void
    {
        $owner = User::factory()->create();

        $firstSameTitleBook = $this->createBook(
            $owner,
            1,
            'Aタイトル',
            '2026-09-01 00:00:00'
        );
        $secondSameTitleBook = $this->createBook(
            $owner,
            2,
            'Aタイトル',
            '2026-09-03 00:00:00'
        );
        $lastBook = $this->createBook(
            $owner,
            3,
            'Bタイトル',
            '2026-09-02 00:00:00'
        );

        $this->get(route('books.index', ['sort' => 'title']))
            ->assertOk()
            ->assertViewHas('books', function ($books) use (
                $firstSameTitleBook,
                $secondSameTitleBook,
                $lastBook
            ): bool {
                return $books->pluck('id')->all() === [
                    $firstSameTitleBook->id,
                    $secondSameTitleBook->id,
                    $lastBook->id,
                ];
            });
    }

    public function test_rating_sort_uses_average_review_count_and_id_priority(): void
    {
        $owner = User::factory()->create();

        $highestRatedBook = $this->createBook(
            $owner,
            1,
            '平均5の書籍',
            '2026-09-01 00:00:00'
        );
        $moreReviewsBook = $this->createBook(
            $owner,
            2,
            '平均4でレビュー2件の書籍',
            '2026-09-01 00:00:00'
        );
        $firstSameRatingBook = $this->createBook(
            $owner,
            3,
            '平均4でレビュー1件の書籍A',
            '2026-09-01 00:00:00'
        );
        $secondSameRatingBook = $this->createBook(
            $owner,
            4,
            '平均4でレビュー1件の書籍B',
            '2026-09-01 00:00:00'
        );
        $bookWithoutReviews = $this->createBook(
            $owner,
            5,
            'レビューなしの書籍',
            '2026-09-01 00:00:00'
        );

        $this->createReview($highestRatedBook, 5);
        $this->createReview($moreReviewsBook, 4);
        $this->createReview($moreReviewsBook, 4);
        $this->createReview($firstSameRatingBook, 4);
        $this->createReview($secondSameRatingBook, 4);

        $this->get(route('books.index', ['sort' => 'rating']))
            ->assertOk()
            ->assertViewHas('books', function ($books) use (
                $highestRatedBook,
                $moreReviewsBook,
                $firstSameRatingBook,
                $secondSameRatingBook,
                $bookWithoutReviews
            ): bool {
                return $books->pluck('id')->all() === [
                    $highestRatedBook->id,
                    $moreReviewsBook->id,
                    $firstSameRatingBook->id,
                    $secondSameRatingBook->id,
                    $bookWithoutReviews->id,
                ];
            });
    }

    public function test_invalid_sort_returns_japanese_validation_error(): void
    {
        $this->get(route('books.index', [
            'sort' => 'invalid',
        ]))->assertSessionHasErrors([
            'sort' => '並び順の指定が正しくありません。',
        ]);

        $this->get(route('books.index', [
            'sort' => ['latest'],
        ]))->assertSessionHasErrors([
            'sort' => '並び順の指定が正しくありません。',
        ]);
    }

    private function createBook(
        User $owner,
        int $sequence,
        string $title,
        string $createdAt
    ): Book {
        $book = $owner->books()->create([
            'title' => $title,
            'author' => 'テスト著者',
            'isbn' => sprintf('978420000%04d', $sequence),
            'published_date' => '2026-09-26',
            'description' => '並び替えテスト用の説明です。',
            'image_url' => null,
        ]);

        $book->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $book;
    }

    private function createReview(Book $book, int $rating): Review
    {
        $reviewer = User::factory()->create();

        $review = new Review([
            'rating' => $rating,
            'comment' => '並び替えテスト用レビューです。',
        ]);
        $review->user()->associate($reviewer);
        $review->book()->associate($book);
        $review->save();

        return $review;
    }
}
