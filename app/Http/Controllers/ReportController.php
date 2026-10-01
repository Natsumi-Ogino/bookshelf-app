<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * ログインユーザーのレビューから読書統計を集計して表示します。
     *
     * @param  Request  $request  認証ユーザーを含むリクエスト
     * @return View マイ読書レポート画面
     */
    public function __invoke(Request $request): View
    {
        $reviews = $request->user()
            ->reviews()
            ->with('book.genres')
            ->get();

        $totalReviews = $reviews->count();

        $ratingDistribution = collect(range(1, 5))
            ->map(
                fn (int $rating): int => $reviews
                    ->where('rating', $rating)
                    ->count()
            );

        $topRatedBooks = $reviews
            ->where('rating', '>=', 4)
            ->sortBy([
                ['rating', 'desc'],
                ['created_at', 'desc'],
                ['book_id', 'asc'],
            ])
            ->take(5)
            ->map(fn (Review $review): array => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ])
            ->values();

        $genreRatings = $reviews
            ->flatMap(
                fn (Review $review): Collection => $review->book->genres
                    ->map(fn (Genre $genre): array => [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                    ])
            )
            ->groupBy('id')
            ->map(function (Collection $items): array {
                return [
                    'id' => $items->first()['id'],
                    'name' => $items->first()['name'],
                    'count' => $items->count(),
                    'average_rating' => $items->avg('rating'),
                ];
            })
            ->sortBy([
                ['average_rating', 'desc'],
                ['count', 'desc'],
                ['id', 'asc'],
            ])
            ->take(5)
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $reviews
                    ->pluck('book_id')
                    ->unique()
                    ->count(),
                'average_rating' => $reviews->avg('rating'),
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
