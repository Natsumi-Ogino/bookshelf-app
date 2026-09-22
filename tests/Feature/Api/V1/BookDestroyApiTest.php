<?php

namespace Tests\Feature\Api\V1;

use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDestroyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_delete_book_and_related_records_with_empty_204_response(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $genre = Genre::query()->create(['name' => '小説']);

        $book = $owner->books()->create([
            'title' => '削除対象の書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-20',
            'description' => null,
            'image_url' => null,
        ]);

        $book->genres()->attach($genre);
        $reviewer->favoriteBooks()->attach($book);

        $review = new Review([
            'rating' => 4,
            'comment' => 'テストレビュー',
        ]);
        $review->user()->associate($reviewer);
        $review->book()->associate($book);
        $review->save();
        $review->likedByUsers()->attach($owner);

        $this->deleteJson("/api/v1/books/{$book->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('users', ['id' => $reviewer->id]);
    }

    public function test_missing_book_returns_only_approved_404_error(): void
    {
        $this->deleteJson('/api/v1/books/999999')
            ->assertStatus(404)
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    public function test_book_identifier_with_numeric_prefix_does_not_delete_book(): void
    {
        $owner = User::factory()->create();
        $book = $owner->books()->create([
            'title' => '削除しない書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-20',
            'description' => null,
            'image_url' => null,
        ]);

        $this->deleteJson("/api/v1/books/{$book->id}invalid")
            ->assertStatus(404)
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}
