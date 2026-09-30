<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookNullableFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_user_can_create_book_without_isbn_and_published_date(): void
    {
        $user = User::factory()->create();
        $genre = Genre::query()->create([
            'name' => '小説',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'ISBNと出版日がない書籍',
                'author' => 'テスト著者',
                'isbn' => '',
                'published_date' => '',
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]);

        $book = Book::query()
            ->where('title', 'ISBNと出版日がない書籍')
            ->firstOrFail();

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', '書籍を登録しました。');

        $this->assertNull($book->isbn);
        $this->assertNull($book->published_date);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => null,
            'published_date' => null,
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSeeTextInOrder([
                'ISBN:',
                '未登録',
                '出版日:',
                '未登録',
            ]);
    }

    public function test_web_owner_can_update_isbn_and_published_date_to_null(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::query()->create([
            'name' => '歴史',
        ]);

        $book = $owner->books()->create([
            'title' => '更新前の書籍',
            'author' => '更新前の著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-20',
            'description' => null,
            'image_url' => null,
        ]);

        $book->genres()->attach($genre);

        $response = $this
            ->actingAs($owner)
            ->put(route('books.update', $book), [
                'title' => '更新後の書籍',
                'author' => '更新後の著者',
                'isbn' => '',
                'published_date' => '',
                'description' => null,
                'image_url' => null,
                'genres' => [$genre->id],
            ]);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', '書籍を更新しました。');

        $book->refresh();

        $this->assertNull($book->isbn);
        $this->assertNull($book->published_date);

        $this->actingAs($owner)
            ->get(route('books.edit', $book))
            ->assertOk()
            ->assertSeeText('ISBN-13')
            ->assertSeeText('出版日')
            ->assertSeeText('（任意）');
    }

    public function test_api_store_requires_isbn_and_published_date(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::query()->create([
            'name' => '料理',
        ]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'API登録書籍',
            'author' => 'API著者',
            'genres' => [$genre->id],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.isbn.0',
                'ISBNは必須です。'
            )
            ->assertJsonPath(
                'errors.published_date.0',
                '出版日は必須です。'
            );

        $this->assertDatabaseMissing('books', [
            'title' => 'API登録書籍',
        ]);
    }

    public function test_api_update_requires_isbn_and_published_date(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::query()->create([
            'name' => '技術',
        ]);

        $book = $owner->books()->create([
            'title' => 'API更新前書籍',
            'author' => 'API更新前著者',
            'isbn' => '9784000000002',
            'published_date' => '2026-09-20',
            'description' => null,
            'image_url' => null,
        ]);

        $book->genres()->attach($genre);
        Sanctum::actingAs($owner);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            [
                'title' => 'API更新後書籍',
                'author' => 'API更新後著者',
                'genres' => [$genre->id],
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.isbn.0',
                'ISBNは必須です。'
            )
            ->assertJsonPath(
                'errors.published_date.0',
                '出版日は必須です。'
            );

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'API更新前書籍',
            'isbn' => '9784000000002',
            'published_date' => '2026-09-20',
        ]);
    }

    public function test_api_show_returns_null_for_nullable_book_fields(): void
    {
        $owner = User::factory()->create();

        $book = $owner->books()->create([
            'title' => 'NULL項目を持つ書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.isbn', null)
            ->assertJsonPath('data.published_date', null);
    }
}
