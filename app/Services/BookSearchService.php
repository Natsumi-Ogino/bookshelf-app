<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;

class BookSearchService
{
    /**
     * 書籍一覧とCSV出力で共通利用する検索・絞り込み・並び順を適用します。
     *
     * @param  array{keyword?: string|null, genre?: int|string|null, sort?: string|null}  $filters
     * @return Builder<Book>
     */
    public function query(array $filters): Builder
    {
        $sort = $filters['sort'] ?? 'latest';

        $booksQuery = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword): void {
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['genre'] ?? null, function (Builder $query, int|string $genreId): void {
                $query->whereHas(
                    'genres',
                    fn (Builder $genreQuery) => $genreQuery->whereKey($genreId)
                );
            });

        return match ($sort) {
            'oldest' => $booksQuery
                ->orderBy('created_at')
                ->orderBy('id'),
            'title' => $booksQuery
                ->orderBy('title')
                ->orderBy('id'),
            'rating' => $booksQuery
                ->orderByRaw('CASE WHEN reviews_avg_rating IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->orderBy('id'),
            default => $booksQuery
                ->orderByDesc('created_at')
                ->orderBy('id'),
        };
    }
}
