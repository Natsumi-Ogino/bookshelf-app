<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_book_index_and_detail_with_ten_books_per_page(): void
    {
        $user = User::factory()->create();

        for ($sequence = 1; $sequence <= 11; $sequence++) {
            $this->createBook($user, $sequence);
        }

        $book = Book::query()->firstOrFail();

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewIs('books.index')
            ->assertViewHas('books', function ($books): bool {
                return $books->count() === 10
                    && $books->total() === 11;
            });

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertViewIs('books.show')
            ->assertViewHas('book', fn (Book $viewBook): bool => $viewBook->is($book));
    }

    public function test_guest_cannot_manage_books(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner);

        $this->get(route('books.create'))
            ->assertRedirect(route('login'));

        $this->post(route('books.store'))
            ->assertRedirect(route('login'));

        $this->get(route('books.edit', $book))
            ->assertRedirect(route('login'));

        $this->put(route('books.update', $book))
            ->assertRedirect(route('login'));

        $this->delete(route('books.destroy', $book))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_authenticated_user_can_create_book_with_genres(): void
    {
        $user = User::factory()->create();
        $genreOne = Genre::query()->create(['name' => '小説']);
        $genreTwo = Genre::query()->create(['name' => '歴史']);

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => '新しい書籍',
                'author' => '著者名',
                'isbn' => '9784000000100',
                'published_date' => '2026-09-19',
                'description' => '書籍の説明です。',
                'image_url' => 'https://example.com/book.jpg',
                'genres' => [$genreOne->id, $genreTwo->id],
            ]);

        $book = Book::query()
            ->where('isbn', '9784000000100')
            ->firstOrFail();

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', '書籍を登録しました。');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '新しい書籍',
            'author' => '著者名',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genreOne->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genreTwo->id,
        ]);
    }

    public function test_book_validation_rejects_invalid_and_duplicate_values(): void
    {
        $user = User::factory()->create();
        $genre = Genre::query()->create(['name' => '小説']);

        $this->actingAs($user)
            ->post(route('books.store'), [
                'title' => '',
                'author' => '',
                'isbn' => '123',
                'published_date' => 'invalid-date',
                'image_url' => 'invalid-url',
                'genres' => [],
            ])
            ->assertSessionHasErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'image_url',
                'genres',
            ]);

        $existingBook = $this->createBook($user);

        $this->actingAs($user)
            ->post(route('books.store'), [
                'title' => '重複ISBN書籍',
                'author' => '著者名',
                'isbn' => $existingBook->isbn,
                'published_date' => '2026-09-19',
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ])
            ->assertSessionHasErrors([
                'isbn' => 'このISBNは既に登録されています。',
            ]);

        $this->assertDatabaseCount('books', 1);
    }

    public function test_book_owner_can_edit_update_and_delete_book_with_related_data(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $oldGenre = Genre::query()->create(['name' => '小説']);
        $newGenre = Genre::query()->create(['name' => '歴史']);
        $book = $this->createBook($owner);
        $book->genres()->sync([$oldGenre->id]);
        $review = $this->createReview($owner, $book);
        $owner->favoriteBooks()->attach($book);
        $otherUser->likedReviews()->attach($review);

        $this->actingAs($owner)
            ->get(route('books.edit', $book))
            ->assertOk()
            ->assertViewIs('books.edit')
            ->assertViewHas('book', $book);

        $this->actingAs($owner)
            ->put(route('books.update', $book), [
                'title' => '更新後の書籍',
                'author' => '更新後の著者',
                'isbn' => $book->isbn,
                'published_date' => '2026-09-20',
                'description' => '更新後の説明です。',
                'image_url' => null,
                'genres' => [$newGenre->id],
            ])
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', '書籍を更新しました。');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後の書籍',
            'author' => '更新後の著者',
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('books.destroy', $book))
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success', '書籍を削除しました。');

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }

    public function test_non_owner_cannot_edit_update_or_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::query()->create(['name' => '小説']);
        $book = $this->createBook($owner);

        $this->actingAs($otherUser)
            ->get(route('books.edit', $book))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->put(route('books.update', $book), [
                'title' => '不正な更新',
                'author' => '不正な著者',
                'isbn' => $book->isbn,
                'published_date' => '2026-09-19',
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ])
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('books.destroy', $book))
            ->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'テスト書籍1',
            'user_id' => $owner->id,
        ]);
    }

    private function createBook(User $user, int $sequence = 1): Book
    {
        return $user->books()->create([
            'title' => "テスト書籍{$sequence}",
            'author' => 'テスト著者',
            'isbn' => sprintf('978400000%04d', $sequence),
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
