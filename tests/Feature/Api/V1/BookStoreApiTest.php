<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_store_book_as_owner_with_genres(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $firstGenre = Genre::query()->create(['name' => '小説']);
        $secondGenre = Genre::query()->create(['name' => '料理']);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $otherUser->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-20',
            'description' => 'テスト用の説明です。',
            'image_url' => null,
            'genres' => [$firstGenre->id, $secondGenre->id],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'テスト書籍')
            ->assertJsonPath('data.published_date', '2026-09-20')
            ->assertJsonPath('data.average_rating', null)
            ->assertJsonPath('data.review_count', 0)
            ->assertJsonCount(2, 'data.genres');

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
        ], array_keys($response->json('data')));

        $bookId = $response->json('data.id');

        $this->assertDatabaseHas('books', [
            'id' => $bookId,
            'user_id' => $owner->id,
            'isbn' => '9784000000001',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $bookId,
            'genre_id' => $firstGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $bookId,
            'genre_id' => $secondGenre->id,
        ]);
    }

    public function test_guest_cannot_store_book(): void
    {
        $genre = Genre::query()->create(['name' => '小説']);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-20',
            'genres' => [$genre->id],
        ]);

        $response
            ->assertUnauthorized()
            ->assertExactJson([
                'error' => '認証が必要です。',
            ]);

        $this->assertSame(0, Book::query()->count());
    }

    public function test_invalid_genre_returns_422_without_creating_book(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-20',
            'genres' => [999999],
        ]);

        $response
            ->assertStatus(422);

        $this->assertSame(
            '選択されたジャンルは存在しません。',
            $response->json('errors')['genres.0'][0]
        );

        $this->assertSame(0, Book::query()->count());
    }
}
