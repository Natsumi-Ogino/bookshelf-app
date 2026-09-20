<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_genre_routes(): void
    {
        $genre = Genre::query()->create([
            'name' => '小説',
        ]);

        $this->get(route('genres.index'))
            ->assertRedirect(route('login'));

        $this->get(route('genres.create'))
            ->assertRedirect(route('login'));

        $this->post(route('genres.store'), [
            'name' => '歴史',
        ])->assertRedirect(route('login'));

        $this->get(route('genres.show', $genre))
            ->assertRedirect(route('login'));

        $this->get(route('genres.edit', $genre))
            ->assertRedirect(route('login'));

        $this->put(route('genres.update', $genre), [
            'name' => '更新後',
        ])->assertRedirect(route('login'));

        $this->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_index_shows_book_counts_and_detail_paginates_ten_books(): void
    {
        $user = User::factory()->create();
        $genre = Genre::query()->create([
            'name' => '小説',
        ]);

        for ($sequence = 1; $sequence <= 11; $sequence++) {
            $book = $this->createBook($user, $sequence);
            $book->genres()->attach($genre);
        }

        $this->actingAs($user)
            ->get(route('genres.index'))
            ->assertOk()
            ->assertViewIs('genres.index')
            ->assertViewHas('genres', function ($genres) use ($genre): bool {
                $viewGenre = $genres->firstWhere('id', $genre->id);

                return $viewGenre !== null
                    && $viewGenre->books_count === 11;
            });

        $this->actingAs($user)
            ->get(route('genres.show', $genre))
            ->assertOk()
            ->assertViewIs('genres.show')
            ->assertViewHas('genre', fn (Genre $viewGenre): bool => $viewGenre->is($genre))
            ->assertViewHas('books', function ($books): bool {
                return $books->count() === 10
                    && $books->total() === 11
                    && $books->every(
                        fn (Book $book): bool => $book->relationLoaded('genres')
                    );
            });
    }

    public function test_authenticated_user_can_create_update_and_delete_genre(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('genres.create'))
            ->assertOk()
            ->assertViewIs('genres.create');

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '新規ジャンル',
            ])
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを登録しました。');

        $genre = Genre::query()
            ->where('name', '新規ジャンル')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('genres.edit', $genre))
            ->assertOk()
            ->assertViewIs('genres.edit')
            ->assertViewHas('genre', $genre);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '更新後ジャンル',
            ])
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを更新しました。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後ジャンル',
        ]);

        $this->actingAs($user)
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_genre_validation_rejects_invalid_and_duplicate_names(): void
    {
        $user = User::factory()->create();
        $genre = Genre::query()->create([
            'name' => '小説',
        ]);

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '',
            ])
            ->assertSessionHasErrors([
                'name' => 'ジャンル名は必須です。',
            ]);

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => str_repeat('あ', 256),
            ])
            ->assertSessionHasErrors([
                'name' => 'ジャンル名は255文字以内で入力してください。',
            ]);

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '小説',
            ])
            ->assertSessionHasErrors([
                'name' => 'そのジャンル名は既に使用されています。',
            ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '小説',
            ])
            ->assertRedirect(route('genres.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::query()->create([
            'name' => '小説',
        ]);
        $book = $this->createBook($user, 1);
        $book->genres()->attach($genre);

        $this->actingAs($user)
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas(
                'error',
                '書籍が紐づいているため、このジャンルは削除できません。'
            );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    private function createBook(User $owner, int $sequence): Book
    {
        return $owner->books()->create([
            'title' => "ジャンルテスト書籍{$sequence}",
            'author' => 'テスト著者',
            'isbn' => sprintf('978400002%04d', $sequence),
            'published_date' => '2026-09-20',
            'description' => 'ジャンル機能のテスト用書籍です。',
            'image_url' => null,
        ]);
    }
}
