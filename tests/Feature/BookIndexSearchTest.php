<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_search_books_by_partial_title(): void
    {
        $owner = User::factory()->create();
        $matchingBook = $this->createBook($owner, 1, 'Laravel入門', '山田太郎');
        $this->createBook($owner, 2, 'PHP入門', '佐藤花子');

        $this->get(route('books.index', ['keyword' => 'Laravel']))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($matchingBook): bool {
                return $books->total() === 1
                    && $books->first()->is($matchingBook);
            });
    }

    public function test_guest_can_search_books_by_partial_author(): void
    {
        $owner = User::factory()->create();
        $matchingBook = $this->createBook($owner, 1, 'Laravel入門', '山田太郎');
        $this->createBook($owner, 2, 'PHP入門', '佐藤花子');

        $this->get(route('books.index', ['keyword' => '山田']))
            ->assertOk()
            ->assertViewHas('books', function ($books) use ($matchingBook): bool {
                return $books->total() === 1
                    && $books->first()->is($matchingBook);
            });
    }

    public function test_keyword_is_trimmed_and_kept_in_pagination_links(): void
    {
        $owner = User::factory()->create();

        for ($sequence = 1; $sequence <= 11; $sequence++) {
            $this->createBook($owner, $sequence, "PHP書籍{$sequence}", '著者');
        }

        $response = $this->get(route('books.index', ['keyword' => '  PHP  ']));

        $response
            ->assertOk()
            ->assertViewHas('books', function ($books): bool {
                return $books->count() === 10
                    && $books->total() === 11
                    && str_contains($books->nextPageUrl(), 'keyword=PHP');
            });
    }

    public function test_blank_keyword_is_treated_as_no_search_condition(): void
    {
        $owner = User::factory()->create();
        $this->createBook($owner, 1, 'Laravel入門', '山田太郎');
        $this->createBook($owner, 2, 'PHP入門', '佐藤花子');

        $this->get(route('books.index', ['keyword' => '   ']))
            ->assertOk()
            ->assertViewHas('books', fn ($books): bool => $books->total() === 2);
    }

    public function test_no_books_are_returned_when_keyword_does_not_match(): void
    {
        $owner = User::factory()->create();
        $this->createBook($owner, 1, 'Laravel入門', '山田太郎');

        $this->get(route('books.index', ['keyword' => '存在しない検索語']))
            ->assertOk()
            ->assertViewHas('books', fn ($books): bool => $books->total() === 0)
            ->assertSee('書籍が見つかりませんでした。');
    }

    public function test_invalid_keyword_returns_japanese_validation_errors(): void
    {
        $this->get(route('books.index', [
            'keyword' => str_repeat('あ', 256),
        ]))->assertSessionHasErrors([
            'keyword' => 'キーワードは255文字以内で入力してください。',
        ]);

        $this->get(route('books.index', [
            'keyword' => ['Laravel'],
        ]))->assertSessionHasErrors([
            'keyword' => 'キーワードは文字列で入力してください。',
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
            'isbn' => sprintf('978400000%04d', $sequence),
            'published_date' => '2026-09-26',
            'description' => '検索テスト用の説明です。',
            'image_url' => null,
        ]);
    }
}
