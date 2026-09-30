<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_report_aggregates_only_authenticated_users_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $owner = User::factory()->create();

        $firstBook = $this->createBook($owner, 1);
        $secondBook = $this->createBook($owner, 2);
        $otherBook = $this->createBook($owner, 3);

        $this->createReview($user, $firstBook, 5);
        $this->createReview($user, $secondBook, 3);
        $this->createReview($otherUser, $otherBook, 1);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewIs('reports.index')
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['summary'] === [
                    'total_reviews' => 2,
                    'books_read' => 2,
                    'average_rating' => 4,
                ]
                    && $stats['rating_distribution']->all() === [0, 0, 1, 0, 1];
            })
            ->assertSee('style="width: 50%"', false)
            ->assertSeeText('読書レポート');
    }

    public function test_top_rated_books_are_filtered_sorted_and_limited_to_five(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $reviews = collect();
        $ratings = [5, 5, 5, 4, 4, 4, 3];

        foreach ($ratings as $index => $rating) {
            $reviews->push($this->createReview(
                $user,
                $this->createBook($owner, $index + 1),
                $rating,
                Carbon::parse('2026-09-01')->addDays($index)
            ));
        }

        $expectedBookIds = [
            $reviews[2]->book_id,
            $reviews[1]->book_id,
            $reviews[0]->book_id,
            $reviews[5]->book_id,
            $reviews[4]->book_id,
        ];

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) use ($expectedBookIds): bool {
                return $stats['top_rated_books']->pluck('id')->all() === $expectedBookIds
                    && $stats['top_rated_books']->count() === 5
                    && $stats['top_rated_books']->every(
                        fn (array $book): bool => $book['rating'] >= 4
                    );
            });
    }

    public function test_genre_ratings_include_each_genre_and_use_approved_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $owner = User::factory()->create();
        $genreA = Genre::query()->create(['name' => 'ジャンルA']);
        $genreB = Genre::query()->create(['name' => 'ジャンルB']);
        $genreC = Genre::query()->create(['name' => 'ジャンルC']);

        $firstBook = $this->createBook($owner, 1);
        $firstBook->genres()->attach([$genreA->id, $genreB->id]);
        $secondBook = $this->createBook($owner, 2);
        $secondBook->genres()->attach($genreA->id);
        $thirdBook = $this->createBook($owner, 3);
        $thirdBook->genres()->attach($genreC->id);

        $this->createReview($user, $firstBook, 5);
        $this->createReview($user, $secondBook, 3);
        $this->createReview($user, $thirdBook, 4);
        $this->createReview($otherUser, $firstBook, 1);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats) use ($genreA, $genreB, $genreC): bool {
                return $stats['genre_ratings']->all() === [
                    [
                        'id' => $genreB->id,
                        'name' => 'ジャンルB',
                        'count' => 1,
                        'average_rating' => 5,
                    ],
                    [
                        'id' => $genreA->id,
                        'name' => 'ジャンルA',
                        'count' => 2,
                        'average_rating' => 4,
                    ],
                    [
                        'id' => $genreC->id,
                        'name' => 'ジャンルC',
                        'count' => 1,
                        'average_rating' => 4,
                    ],
                ];
            });
    }

    public function test_report_displays_empty_state_when_user_has_no_reviews(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['summary'] === [
                    'total_reviews' => 0,
                    'books_read' => 0,
                    'average_rating' => null,
                ]
                    && $stats['rating_distribution']->all() === [0, 0, 0, 0, 0]
                    && $stats['top_rated_books']->isEmpty()
                    && $stats['genre_ratings']->isEmpty();
            })
            ->assertSeeText('レビューを投稿すると、ここに読書記録が表示されます。')
            ->assertSeeText('4星以上の書籍がありません')
            ->assertSeeText('ジャンルが設定された書籍のレビューがありません');
    }

    private function createBook(User $owner, int $sequence): Book
    {
        return $owner->books()->create([
            'title' => "読書レポート書籍{$sequence}",
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);
    }

    private function createReview(
        User $reviewer,
        Book $book,
        int $rating,
        ?Carbon $createdAt = null
    ): Review {
        $review = new Review([
            'rating' => $rating,
            'comment' => '読書レポートテスト用のレビューです。',
        ]);

        $review->user()->associate($reviewer);
        $review->book()->associate($book);

        if ($createdAt !== null) {
            $review->created_at = $createdAt;
            $review->updated_at = $createdAt;
        }

        $review->save();

        return $review;
    }
}
