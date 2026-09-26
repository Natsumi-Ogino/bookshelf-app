<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexGenreFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_genres_are_displayed_in_name_order(): void
    {
        Genre::query()->create(['name' => 'Bジャンル']);
        Genre::query()->create(['name' => 'Aジャンル']);

        $this->get(route('books.index'))
            ->assertOk()
            ->assertViewHas('genres', function ($genres): bool {
                return $genres->pluck('name')->all() === [
                    'Aジャンル',
                    'Bジャンル',
                ];
            });
    }

    public function test_guest_can_filter_books_by_genre(): void
    {
        $owner = User::factory()->create();
        $technology = Genre::query()->create(['name' => '技術書']);
        $novel = Genre::query()->create(['name' => '小説']);

        $matchingBook = $this->createBook($owner, 1, 'Laravel入門', '山田太郎');
        $matchingBook->genres()->attach($technology);

        $otherBook = $this->createBook($owner, 2, '吾輩は猫である', '夏目漱石');
        $otherBook->genres()->attach($novel);

        $this->get(route('books.index', ['genre' => $technology->id]))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($matchingBook): bool {
                return $books->total() === 1
                    && $books->first()->is($matchingBook);
            });
    }

    public function test_keyword_and_genre_filters_work_together(): void
    {
        $owner = User::factory()->create();
        $technology = Genre::query()->create(['name' => '技術書']);
        $novel = Genre::query()->create(['name' => '小説']);

        $matchingBook = $this->createBook($owner, 1, 'PHP入門', '山田太郎');
        $matchingBook->genres()->attach($technology);

        $differentGenreBook = $this->createBook($owner, 2, 'PHP実践', '佐藤花子');
        $differentGenreBook->genres()->attach($novel);

        $differentKeywordBook = $this->createBook($owner, 3, 'Laravel入門', '鈴木一郎');
        $differentKeywordBook->genres()->attach($technology);

        $this->get(route('books.index', [
            'keyword' => 'PHP',
            'genre' => $technology->id,
        ]))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($matchingBook): bool {
                return $books->total() === 1
                    && $books->first()->is($matchingBook);
            });
    }

    public function test_blank_genre_is_treated_as_no_filter(): void
    {
        $owner = User::factory()->create();
        $this->createBook($owner, 1, 'Laravel入門', '山田太郎');
        $this->createBook($owner, 2, 'PHP入門', '佐藤花子');

        $this->get(route('books.index', ['genre' => '']))
            ->assertOk()
            ->assertViewHas('books', fn ($books): bool => $books->total() === 2);
    }

    public function test_genre_is_kept_in_pagination_links(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::query()->create(['name' => '技術書']);

        for ($sequence = 1; $sequence <= 11; $sequence++) {
            $book = $this->createBook(
                $owner,
                $sequence,
                "技術書{$sequence}",
                '著者'
            );
            $book->genres()->attach($genre);
        }

        $this->get(route('books.index', ['genre' => $genre->id]))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($genre): bool {
                return $books->count() === 10
                    && $books->total() === 11
                    && str_contains(
                        $books->nextPageUrl(),
                        "genre={$genre->id}"
                    );
            });
    }

    public function test_invalid_genre_returns_japanese_validation_error(): void
    {
        $this->get(route('books.index', [
            'genre' => 999999,
        ]))->assertSessionHasErrors([
            'genre' => '選択されたジャンルが存在しません。',
        ]);

        $this->get(route('books.index', [
            'genre' => '不正な値',
        ]))->assertSessionHasErrors([
            'genre' => '選択されたジャンルが存在しません。',
        ]);
    }

    private function createBook(
        User $owner,
        int $sequence,
        string $title,
        string $author
    ): Book {
        return $owner->books()->create([
            'title' => $title,
            'author' => $author,
            'isbn' => sprintf('978410000%04d', $sequence),
            'published_date' => '2026-09-26',
            'description' => 'ジャンル絞り込みテスト用の説明です。',
            'image_url' => null,
        ]);
    }
}
