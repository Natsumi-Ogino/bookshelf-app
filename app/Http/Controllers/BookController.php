<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexBookRequest $request): View
    {
        $filters = $request->validated();
        $sort = $filters['sort'] ?? 'latest';

        $genres = Genre::query()
            ->orderBy('name')
            ->get();

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

        $booksQuery = match ($sort) {
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

        $books = $booksQuery
            ->paginate(10)
            ->appends($filters);

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Book::class);

        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $this->authorize('create', Book::class);

        $validated = $request->validated();
        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book = DB::transaction(function () use ($request, $validated, $genreIds): Book {
            $book = $request->user()
                ->books()
                ->create($validated);

            $book->genres()->sync($genreIds);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews' => fn ($query) => $query
                ->with(['user', 'likedByUsers'])
                ->latest(),
        ]);

        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $book->load('genres');

        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();
        $genreIds = $validated['genres'];
        unset($validated['genres']);

        DB::transaction(function () use ($book, $validated, $genreIds): void {
            $book->update($validated);
            $book->genres()->sync($genreIds);
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
