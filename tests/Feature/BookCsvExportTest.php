<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_index_displays_csv_link_with_current_filters(): void
    {
        $genre = Genre::query()->create(['name' => '技術書']);
        $filters = [
            'keyword' => 'Laravel',
            'genre' => $genre->id,
            'sort' => 'rating',
        ];

        $this->get(route('books.index', $filters))
            ->assertOk()
            ->assertSeeText('CSV出力')
            ->assertSee(route('books.export.csv', $filters));
    }

    public function test_guest_can_download_filtered_csv_with_approved_columns(): void
    {
        Carbon::setTestNow('2026-10-03 12:34:56 Asia/Tokyo');

        $owner = User::factory()->create();
        $technology = Genre::query()->create(['name' => '技術書']);
        $business = Genre::query()->create(['name' => 'ビジネス']);

        $matchingBook = $this->createBook($owner, 1, [
            'title' => 'Laravel実践',
            'author' => '山田太郎',
            'isbn' => '9784101010014',
            'published_date' => '2026-10-01',
        ]);
        $matchingBook->genres()->attach([$technology->id, $business->id]);
        User::factory()->create()->reviews()->create([
            'book_id' => $matchingBook->id,
            'rating' => 4,
            'comment' => '評価4のレビュー',
        ]);
        User::factory()->create()->reviews()->create([
            'book_id' => $matchingBook->id,
            'rating' => 5,
            'comment' => '評価5のレビュー',
        ]);

        $excludedBook = $this->createBook($owner, 2, [
            'title' => '別の書籍',
            'author' => '別の著者',
        ]);
        $excludedBook->genres()->attach($technology);

        $response = $this->get(route('books.export.csv', [
            'keyword' => 'Laravel',
            'genre' => $technology->id,
            'sort' => 'rating',
        ]));

        $response->assertOk()
            ->assertDownload('books_20261003_123456.csv')
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $rows = $this->csvRows($response->streamedContent());

        $this->assertSame([
            'タイトル',
            '著者',
            'ISBN',
            '出版日',
            'ジャンル',
            '平均評価',
            'レビュー件数',
        ], $rows[0]);
        $this->assertSame([
            'Laravel実践',
            '山田太郎',
            '9784101010014',
            '2026-10-01',
            '技術書、ビジネス',
            '4.5',
            '2',
        ], $rows[1]);
        $this->assertCount(2, $rows);
    }

    public function test_csv_exports_all_matching_books_without_pagination(): void
    {
        $owner = User::factory()->create();

        for ($sequence = 1; $sequence <= 11; $sequence++) {
            $this->createBook($owner, $sequence, [
                'title' => sprintf('対象書籍%02d', $sequence),
            ]);
        }

        $response = $this->get(route('books.export.csv', [
            'keyword' => '対象書籍',
            'sort' => 'title',
        ]));

        $response->assertOk();

        $rows = $this->csvRows($response->streamedContent());

        $this->assertCount(12, $rows);
        $this->assertSame('対象書籍01', $rows[1][0]);
        $this->assertSame('対象書籍11', $rows[11][0]);
    }

    public function test_csv_uses_empty_values_and_prevents_formula_injection(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::query()->create(['name' => '@危険なジャンル']);
        $book = $this->createBook($owner, 1, [
            'title' => '=1+1',
            'author' => '+SUM(A1:A2)',
            'isbn' => null,
            'published_date' => null,
        ]);
        $book->genres()->attach($genre);

        $response = $this->get(route('books.export.csv'));

        $response->assertOk();

        $rows = $this->csvRows($response->streamedContent());

        $this->assertSame([
            "'=1+1",
            "'+SUM(A1:A2)",
            '',
            '',
            "'@危険なジャンル",
            '',
            '0',
        ], $rows[1]);
    }

    public function test_invalid_filter_does_not_download_csv(): void
    {
        $this->from(route('books.index'))
            ->get(route('books.export.csv', ['sort' => 'invalid']))
            ->assertRedirect(route('books.index'))
            ->assertSessionHasErrors([
                'sort' => '並び順の指定が正しくありません。',
            ]);
    }

    /** @return list<list<string>> */
    private function csvRows(string $content): array
    {
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $content = substr($content, 3);
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            $this->fail('CSV test stream could not be opened.');
        }

        fwrite($stream, $content);
        rewind($stream);

        $rows = [];

        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }

    /** @param  array<string, mixed>  $attributes */
    private function createBook(
        User $owner,
        int $sequence,
        array $attributes = []
    ): Book {
        return $owner->books()->create(array_merge([
            'title' => "テスト書籍{$sequence}",
            'author' => 'テスト著者',
            'isbn' => sprintf('978430000%04d', $sequence),
            'published_date' => '2026-10-01',
            'description' => null,
            'image_url' => null,
        ], $attributes));
    }
}
