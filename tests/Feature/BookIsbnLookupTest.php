<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookIsbnLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_isbn_lookup(): void
    {
        Http::preventStrayRequests();

        $this->getJson(route('books.isbn.lookup', '9784101010014'))
            ->assertUnauthorized();

        Http::assertNothingSent();
    }

    #[DataProvider('invalidIsbnProvider')]
    public function test_invalid_isbn_returns_approved_400_error(
        string $isbn
    ): void {
        Http::preventStrayRequests();

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn.lookup', $isbn))
            ->assertStatus(400)
            ->assertExactJson([
                'error' => 'ISBNは13桁で入力してください。',
            ]);

        Http::assertNothingSent();
    }

    /** @return array<string, array{string}> */
    public static function invalidIsbnProvider(): array
    {
        return [
            'too short' => ['978410101001'],
            'non numeric' => ['97841010100A4'],
            'invalid check digit' => ['9784101010015'],
        ];
    }

    public function test_authenticated_user_can_get_normalized_book_data(): void
    {
        config()->set('services.google_books.key', 'fake-google-books-key');

        Http::preventStrayRequests();
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [[
                    'volumeInfo' => [
                        'title' => '吾輩は猫である',
                        'authors' => ['夏目漱石', '共同著者'],
                        'publishedDate' => '1905-01-01',
                        'description' => '書籍の説明です。',
                        'imageLinks' => [
                            'thumbnail' => 'https://example.com/book.jpg',
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn.lookup', '9784101010014'))
            ->assertOk()
            ->assertExactJson([
                'title' => '吾輩は猫である',
                'author' => '夏目漱石、共同著者',
                'published_date' => '1905-01-01',
                'description' => '書籍の説明です。',
                'image_url' => 'https://example.com/book.jpg',
            ]);

        Http::assertSent(function (Request $request): bool {
            $query = [];
            parse_str(
                (string) parse_url($request->url(), PHP_URL_QUERY),
                $query
            );

            return $request->method() === 'GET'
                && str_starts_with(
                    $request->url(),
                    'https://www.googleapis.com/books/v1/volumes?'
                )
                && ($query['q'] ?? null) === 'isbn:9784101010014'
                && ($query['key'] ?? null) === 'fake-google-books-key'
                && $request->hasHeader('Accept', 'application/json');
        });
    }

    #[DataProvider('publishedDateProvider')]
    public function test_published_date_is_normalized_only_when_complete(
        string $sourceDate,
        ?string $expectedDate
    ): void {
        Http::preventStrayRequests();
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [[
                    'volumeInfo' => [
                        'title' => '日付確認用書籍',
                        'publishedDate' => $sourceDate,
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn.lookup', '9784101010014'))
            ->assertOk()
            ->assertJsonPath('published_date', $expectedDate);
    }

    /** @return array<string, array{string, ?string}> */
    public static function publishedDateProvider(): array
    {
        return [
            'year only' => ['2020', null],
            'year and month' => ['2020-05', null],
            'full date' => ['2020-05-10', '2020-05-10'],
        ];
    }

    public function test_missing_optional_fields_are_returned_as_null(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [['volumeInfo' => []]],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn.lookup', '9784101010014'))
            ->assertOk()
            ->assertExactJson([
                'title' => null,
                'author' => null,
                'published_date' => null,
                'description' => null,
                'image_url' => null,
            ]);
    }

    public function test_missing_book_returns_approved_404_error(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn.lookup', '9784101010014'))
            ->assertNotFound()
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    public function test_google_books_error_returns_approved_500_error(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([], 503),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn.lookup', '9784101010014'))
            ->assertStatus(500)
            ->assertExactJson([
                'error' => 'API通信エラーが発生しました。',
            ]);
    }

    public function test_connection_failure_returns_approved_500_error(): void
    {
        Http::preventStrayRequests();
        Http::fake(fn () => throw new ConnectionException(
            'Simulated connection failure.'
        ));

        $this->actingAs(User::factory()->create())
            ->getJson(route('books.isbn.lookup', '9784101010014'))
            ->assertStatus(500)
            ->assertExactJson([
                'error' => 'API通信エラーが発生しました。',
            ]);
    }

    public function test_lookup_ui_is_visible_on_create_and_edit_pages(): void
    {
        $user = User::factory()->create();
        $book = $user->books()->create([
            'title' => '編集確認用書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);

        $this->actingAs($user)
            ->get(route('books.create'))
            ->assertOk()
            ->assertSeeText('ISBNから書籍情報を自動入力')
            ->assertSee('id="isbn-search"', false);

        $this->actingAs($user)
            ->get(route('books.edit', $book))
            ->assertOk()
            ->assertSeeText('ISBNから書籍情報を自動入力')
            ->assertSee('id="isbn-search"', false);
    }
}
