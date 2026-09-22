<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $books = Book::query()
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
                $query->whereHas('genres', fn (Builder $genreQuery) => $genreQuery->whereKey($genreId));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        return BookResource::collection($books);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $validated['user_id'];
        $genreIds = $validated['genres'];
        unset($validated['user_id'], $validated['genres']);

        $book = DB::transaction(function () use ($userId, $genreIds, $validated): Book {
            $user = User::query()->findOrFail($userId);
            $book = $user->books()->create($validated);
            $book->genres()->sync($genreIds);

            return $book;
        });

        $book->load('genres');
        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    public function show(string $id): BookResource|JsonResponse
    {
        $bookId = filter_var($id, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        $book = $bookId === false
            ? null
            : Book::query()
                ->with(['genres', 'reviews.user'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->find($bookId);

        if ($book === null) {
            return response()->json([
                'error' => '書籍が見つかりませんでした。',
            ], 404);
        }

        return new BookResource($book);
    }

    public function update(UpdateBookRequest $request, string $id): JsonResponse
    {
        $bookId = filter_var($id, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $book = $bookId === false ? null : Book::query()->find($bookId);

        if ($book === null) {
            return response()->json([
                'error' => '書籍が見つかりませんでした。',
            ], 404);
        }

        $validated = $request->validated();
        $userId = $validated['user_id'];
        $genreIds = $validated['genres'];
        unset($validated['user_id'], $validated['genres']);

        DB::transaction(function () use ($book, $userId, $genreIds, $validated): void {
            $user = User::query()->findOrFail($userId);
            $book->fill($validated);
            $book->user()->associate($user);
            $book->save();
            $book->genres()->sync($genreIds);
        });

        $book->load('genres');
        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return (new BookResource($book))->response();
    }

    public function destroy(string $id): Response|JsonResponse
    {
        $bookId = filter_var($id, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $book = $bookId === false ? null : Book::query()->find($bookId);

        if ($book === null) {
            return response()->json([
                'error' => '書籍が見つかりませんでした。',
            ], 404);
        }

        $book->delete();

        return response()->noContent();
    }
}
