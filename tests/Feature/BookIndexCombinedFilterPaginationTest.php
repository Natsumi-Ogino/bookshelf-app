<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexCombinedFilterPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_combined_conditions_are_applied_and_kept_across_pages(): void
    {
        $owner = User::factory()->create();
        $technology = Genre::query()->create(['name' => '技術書']);
        $novel = Genre::query()->create(['name' => '小説']);

        $matchingBooks = collect();

        for ($sequence = 1; $sequence <= 11; $sequence++) {
            $book = $this->createBook(
                $owner,
                $sequence,
                sprintf('PHP書籍%02d', $sequence)
            );
            $book->genres()->attach($technology);
            $matchingBooks->push($book);
        }

        $differentGenreBook = $this->createBook(
            $owner,
            12,
            'PHP小説',
        );
        $differentGenreBook->genres()->attach($novel);

        $differentKeywordBook = $this->createBook(
            $owner,
            13,
            'Laravel書籍',
        );
        $differentKeywordBook->genres()->attach($technology);

        $query = [
            'keyword' => 'PHP',
            'genre' => $technology->id,
            'sort' => 'title',
        ];

        $firstPage = $this->get(route('books.index', $query));

        $firstPage
            ->assertOk()
            ->assertViewHas('books', function ($books) use (
                $matchingBooks,
                $technology
            ): bool {
                $nextPageUrl = $books->nextPageUrl();

                return $books->count() === 10
                    && $books->total() === 11
                    && $books->currentPage() === 1
                    && $books->first()->is($matchingBooks->first())
                    && $books->last()->is($matchingBooks->get(9))
                    && is_string($nextPageUrl)
                    && str_contains($nextPageUrl, 'keyword=PHP')
                    && str_contains(
                        $nextPageUrl,
                        "genre={$technology->id}"
                    )
                    && str_contains($nextPageUrl, 'sort=title')
                    && str_contains($nextPageUrl, 'page=2');
            });

        $secondPage = $this->get(route('books.index', [
            ...$query,
            'page' => 2,
        ]));

        $secondPage
            ->assertOk()
            ->assertViewHas('books', function ($books) use (
                $matchingBooks,
                $technology
            ): bool {
                $previousPageUrl = $books->previousPageUrl();

                return $books->count() === 1
                    && $books->total() === 11
                    && $books->currentPage() === 2
                    && $books->first()->is($matchingBooks->get(10))
                    && is_string($previousPageUrl)
                    && str_contains($previousPageUrl, 'keyword=PHP')
                    && str_contains(
                        $previousPageUrl,
                        "genre={$technology->id}"
                    )
                    && str_contains($previousPageUrl, 'sort=title');
            });
    }

    private function createBook(
        User $owner,
        int $sequence,
        string $title
    ): Book {
        return $owner->books()->create([
            'title' => $title,
            'author' => 'テスト著者',
            'isbn' => sprintf('978430000%04d', $sequence),
            'published_date' => '2026-09-26',
            'description' => '複合検索テスト用の説明です。',
            'image_url' => null,
        ]);
    }
}
