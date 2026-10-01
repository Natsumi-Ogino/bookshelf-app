<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_advanced_books_reviews_and_review_likes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $userIds = User::query()->pluck('id');
        $books = Book::query()
            ->withCount('reviews')
            ->get();
        $reviews = Review::query()
            ->with('likedByUsers')
            ->get();

        $approvedComments = [
            1 => ['残念ながら合いませんでした。', '期待と違いました。'],
            2 => ['少し期待外れでした。', '内容が薄い印象。', 'もう少し深掘りしてほしかった。'],
            3 => ['普通でした。', '可もなく不可もなく。', '期待したほどではなかった。'],
            4 => ['とても参考になりました。', '読みやすくておすすめです。', '期待通りの内容でした。'],
            5 => ['素晴らしい本でした！', '人生が変わりました。', '何度も読み返しています。'],
        ];

        $this->assertCount(11, $books);
        $this->assertTrue($books->every(
            fn (Book $book): bool => $userIds->contains($book->user_id)
                && $book->reviews_count >= 2
                && $book->reviews_count <= 4
        ));
        $this->assertGreaterThanOrEqual(22, $reviews->count());
        $this->assertLessThanOrEqual(44, $reviews->count());
        $this->assertSame([1, 2, 3, 4, 5], $reviews->pluck('rating')->unique()->sort()->values()->all());
        $this->assertTrue($reviews->every(
            fn (Review $review): bool => $userIds->contains($review->user_id)
                && in_array($review->comment, $approvedComments[$review->rating], true)
                && $review->likedByUsers->count() <= 3
                && ! $review->likedByUsers->contains('id', $review->user_id)
        ));
    }
}
