<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_owner_sees_403_page_with_message_and_book_index_link(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = $owner->books()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2026-09-20',
            'description' => null,
            'image_url' => null,
        ]);

        $this->actingAs($otherUser)
            ->get(route('books.edit', $book))
            ->assertStatus(403)
            ->assertSee('この操作を行う権限がありません。')
            ->assertSee('書籍一覧へ戻る')
            ->assertSee('href="'.route('books.index').'"', false);
    }

    public function test_missing_book_shows_404_page_with_message_and_book_index_link(): void
    {
        $this->get('/books/999999')
            ->assertStatus(404)
            ->assertSee('お探しのページが見つかりません。')
            ->assertSee('書籍一覧へ戻る')
            ->assertSee('href="'.route('books.index').'"', false);
    }
}
