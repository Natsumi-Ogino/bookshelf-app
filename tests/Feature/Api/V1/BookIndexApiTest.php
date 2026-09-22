<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_get_books_with_required_fields_and_review_summary(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $genre = Genre::query()->create(['name' => '技術書']);
        $book = $this->createBook($owner, 1, 'PHP入門', '山田太郎');
        $book->genres()->attach($genre);

        $firstReview = new Review([
            'rating' => 4,
            'comment' => '分かりやすい本です。',
        ]);
        $firstReview->user()->associate($owner);
        $firstReview->book()->associate($book);
        $firstReview->save();

        $secondReview = new Review([
            'rating' => 5,
            'comment' => '参考になりました。',
        ]);
        $secondReview->user()->associate($reviewer);
        $secondReview->book()->associate($book);
        $secondReview->save();

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.genres.0.id', $genre->id)
            ->assertJsonPath('data.0.genres.0.name', '技術書')
            ->assertJsonPath('data.0.average_rating', 4.5)
            ->assertJsonPath('data.0.review_count', 2)
            ->assertJsonPath('meta.total', 1);

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
        ], array_keys($response->json('data.0')));
    }

    public function test_keyword_and_genre_filters_work_together(): void
    {
        $owner = User::factory()->create();
        $technology = Genre::query()->create(['name' => '技術書']);
        $cooking = Genre::query()->create(['name' => '料理']);

        $matchingBook = $this->createBook($owner, 1, 'PHP入門', '山田太郎');
        $matchingBook->genres()->attach($technology);

        $authorMatchingBook = $this->createBook($owner, 2, '料理入門', 'PHP講師');
        $authorMatchingBook->genres()->attach($cooking);

        $genreMatchingBook = $this->createBook($owner, 3, '歴史入門', '佐藤花子');
        $genreMatchingBook->genres()->attach($technology);

        $this->getJson('/api/v1/books?keyword=PHP')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson("/api/v1/books?genre={$technology->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson("/api/v1/books?keyword=PHP&genre={$technology->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matchingBook->id);
    }

    public function test_default_pagination_and_newest_first_order(): void
    {
        $owner = User::factory()->create();
        $newestBook = null;

        for ($sequence = 1; $sequence <= 21; $sequence++) {
            $newestBook = $this->createBook($owner, $sequence, "書籍{$sequence}", '著者');
        }

        $this->getJson('/api/v1/books')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('data.0.id', $newestBook->id)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 21);

        $response = $this->getJson('/api/v1/books?per_page=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 21);

        $this->assertStringContainsString('per_page=1', $response->json('links.next'));
    }

    public function test_invalid_query_parameters_return_japanese_json_errors(): void
    {
        $cases = [
            ['keyword='.rawurlencode(str_repeat('あ', 256)), 'keyword', 'キーワードは255文字以内で入力してください。'],
            ['genre=9999', 'genre', '選択されたジャンルが存在しません。'],
            ['page=0', 'page', 'ページ番号は1以上の整数で指定してください。'],
            ['per_page=101', 'per_page', '1ページ当たりの件数は1から100の整数で指定してください。'],
        ];

        foreach ($cases as [$query, $field, $message]) {
            $this->getJson("/api/v1/books?{$query}")
                ->assertStatus(422)
                ->assertJsonPath('message', $message)
                ->assertJsonPath("errors.{$field}.0", $message);
        }
    }

    private function createBook(User $owner, int $sequence, string $title, string $author): Book
    {
        return $owner->books()->create([
            'title' => $title,
            'author' => $author,
            'isbn' => sprintf('978400000%04d', $sequence),
            'published_date' => '2026-09-20',
            'description' => 'テスト用の説明です。',
            'image_url' => null,
        ]);
    }
}
