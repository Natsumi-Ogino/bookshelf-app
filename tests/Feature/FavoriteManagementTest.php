<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_favorite_routes(): void
    {
        $owner = User::factory()->create();
        $book = $this->createBook($owner);

        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));

        $this->post(route('favorites.toggle', $book))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_authenticated_user_can_add_remove_and_readd_favorite(): void
    {
        $user = User::factory()->create();
        $book = $this->createBook($user);
        $sourceUrl = route('books.show', $book);

        $this->actingAs($user)
            ->from($sourceUrl)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect($sourceUrl)
            ->assertSessionHas('success', 'お気に入りに追加しました。');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user)
            ->from($sourceUrl)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect($sourceUrl)
            ->assertSessionHas('success', 'お気に入りを解除しました。');

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user)
            ->from($sourceUrl)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect($sourceUrl)
            ->assertSessionHas('success', 'お気に入りに追加しました。');

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_favorite_index_shows_only_authenticated_users_books_ten_per_page(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $latestFavorite = null;

        for ($sequence = 1; $sequence <= 11; $sequence++) {
            $book = $this->createBook($user, $sequence);
            $user->favoriteBooks()->attach($book);
            $latestFavorite = $book;
        }

        $otherBook = $this->createBook($otherUser, 99);
        $otherUser->favoriteBooks()->attach($otherBook);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertViewIs('favorites.index')
            ->assertViewHas('books', function ($books) use ($latestFavorite, $otherBook): bool {
                return $books->count() === 10
                    && $books->total() === 11
                    && $books->first()->is($latestFavorite)
                    && ! $books->contains(fn (Book $book): bool => $book->is($otherBook));
            });
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
}
