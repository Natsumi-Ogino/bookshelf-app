<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_update_book_owner_and_genres_without_changing_isbn(): void
    {
        $originalOwner = User::factory()->create();
        $newOwner = User::factory()->create();
        $oldGenre = Genre::query()->create(['name' => '小説']);
        $newGenre = Genre::query()->create(['name' => '料理']);
        $book = $this->createBook($originalOwner, '9784000000001');
        $book->genres()->attach($oldGenre);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $newOwner->id,
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-21',
            'description' => '更新後の説明です。',
            'image_url' => null,
            'genres' => [$newGenre->id],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', '更新後の書籍')
            ->assertJsonPath('data.isbn', '9784000000001')
            ->assertJsonPath('data.published_date', '2026-09-21')
            ->assertJsonPath('data.genres.0.id', $newGenre->id)
            ->assertJsonPath('data.average_rating', null)
            ->assertJsonPath('data.review_count', 0)
            ->assertJsonCount(1, 'data.genres');

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

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $newOwner->id,
            'title' => '更新後の書籍',
            'isbn' => '9784000000001',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);
    }

    public function test_duplicate_isbn_returns_422_without_updating_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::query()->create(['name' => '小説']);
        $book = $this->createBook($owner, '9784000000001');
        $otherBook = $this->createBook($owner, '9784000000002');
        $book->genres()->attach($genre);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $owner->id,
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => $otherBook->isbn,
            'published_date' => '2026-09-21',
            'genres' => [$genre->id],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.isbn.0', 'このISBNは既に登録されています。');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前の書籍',
            'isbn' => '9784000000001',
        ]);
    }

    public function test_missing_book_returns_only_approved_404_error(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::query()->create(['name' => '小説']);

        $this->putJson('/api/v1/books/999999', [
            'user_id' => $owner->id,
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-21',
            'genres' => [$genre->id],
        ])
            ->assertStatus(404)
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    public function test_non_numeric_book_identifier_returns_404(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::query()->create(['name' => '小説']);

        $this->putJson('/api/v1/books/not-a-book', [
            'user_id' => $owner->id,
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-21',
            'genres' => [$genre->id],
        ])
            ->assertStatus(404)
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    public function test_book_identifier_with_numeric_prefix_does_not_update_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::query()->create(['name' => '小説']);
        $book = $this->createBook($owner, '9784000000001');

        $this->putJson("/api/v1/books/{$book->id}invalid", [
            'user_id' => $owner->id,
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-09-21',
            'genres' => [$genre->id],
        ])
            ->assertStatus(404)
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前の書籍',
        ]);
    }

    private function createBook(User $owner, string $isbn): Book
    {
        return $owner->books()->create([
            'title' => '更新前の書籍',
            'author' => '更新前の著者',
            'isbn' => $isbn,
            'published_date' => '2026-09-20',
            'description' => null,
            'image_url' => null,
        ]);
    }
}
