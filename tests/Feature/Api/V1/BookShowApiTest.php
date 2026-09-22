<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_get_book_with_genres_reviews_and_summary(): void
    {
        $owner = User::factory()->create(['name' => '登録者']);
        $reviewer = User::factory()->create(['name' => 'レビュー投稿者']);
        $genre = Genre::query()->create(['name' => '小説']);
        $book = $this->createBook($owner);
        $book->genres()->attach($genre);

        $firstReview = $this->createReview($owner, $book, 4, '最初のレビュー');
        $secondReview = $this->createReview($reviewer, $book, 5, '次のレビュー');

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.published_date', '2026-09-20')
            ->assertJsonPath('data.genres.0.name', '小説')
            ->assertJsonPath('data.average_rating', 4.5)
            ->assertJsonPath('data.review_count', 2)
            ->assertJsonCount(2, 'data.reviews');

        $this->assertSame([
            'id',
            'title',
            'author',
            'isbn',
            'published_date',
            'description',
            'image_url',
            'genres',
            'average_rating',
            'review_count',
            'reviews',
        ], array_keys($response->json('data')));

        $reviews = $response->json('data.reviews');

        $this->assertEqualsCanonicalizing(
            [$firstReview->id, $secondReview->id],
            array_column($reviews, 'id')
        );

        foreach ($reviews as $review) {
            $this->assertSame([
                'id',
                'user_name',
                'rating',
                'comment',
                'created_at',
            ], array_keys($review));

            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
                $review['created_at']
            );
        }

        $this->assertEqualsCanonicalizing(
            ['登録者', 'レビュー投稿者'],
            array_column($reviews, 'user_name')
        );
    }

    public function test_book_without_reviews_returns_null_average_and_empty_reviews(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.average_rating', null)
            ->assertJsonPath('data.review_count', 0)
            ->assertJsonCount(0, 'data.reviews');
    }

    public function test_missing_book_returns_only_approved_404_error(): void
    {
        $this->getJson('/api/v1/books/999999')
            ->assertStatus(404)
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    public function test_book_identifier_with_numeric_prefix_returns_404(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner);

        $this->getJson("/api/v1/books/{$book->id}invalid")
            ->assertStatus(404)
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    private function createBook(User $owner): Book
    {
        return $owner->books()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-20',
            'description' => 'テスト用の説明です。',
            'image_url' => null,
        ]);
    }

    private function createReview(
        User $user,
        Book $book,
        int $rating,
        string $comment
    ): Review {
        $review = new Review([
            'rating' => $rating,
            'comment' => $comment,
        ]);

        $review->user()->associate($user);
        $review->book()->associate($book);
        $review->save();

        return $review;
    }
}
