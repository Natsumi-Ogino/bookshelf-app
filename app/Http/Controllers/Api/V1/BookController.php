<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 検索条件に一致する書籍をページネーション形式で返します。
     *
     * @param  IndexBookRequest  $request  検証済みの検索条件を含むリクエスト
     * @return AnonymousResourceCollection 書籍一覧のAPI Resourceコレクション
     */
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

    /**
     * 認証ユーザーを所有者として書籍とジャンル紐付けを登録します。
     *
     * @param  StoreBookRequest  $request  検証済み書籍データを含むリクエスト
     * @return JsonResponse 登録した書籍を含むJSONレスポンス
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book = DB::transaction(function () use ($request, $genreIds, $validated): Book {
            $book = $request->user()->books()->create($validated);
            $book->genres()->sync($genreIds);

            return $book;
        });

        $book->load('genres');
        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    /**
     * 指定された書籍のジャンル、レビュー、評価集計を返します。
     *
     * @param  string  $id  取得対象の書籍ID
     * @return BookResource|JsonResponse 書籍Resourceまたは404エラー
     */
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

    /**
     * 所有者認可後に書籍とジャンル紐付けを更新します。
     *
     * @param  UpdateBookRequest  $request  検証済み書籍データを含むリクエスト
     * @param  string  $id  更新対象の書籍ID
     * @return JsonResponse 更新した書籍または404エラーを含むJSONレスポンス
     */
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

        $this->authorize('update', $book);

        $validated = $request->validated();
        $genreIds = $validated['genres'];
        unset($validated['genres']);

        DB::transaction(function () use ($book, $genreIds, $validated): void {
            $book->fill($validated);
            $book->save();
            $book->genres()->sync($genreIds);
        });

        $book->load('genres');
        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return (new BookResource($book))->response();
    }

    /**
     * 所有者認可後に指定された書籍を削除します。
     *
     * @param  string  $id  削除対象の書籍ID
     * @return Response|JsonResponse 本文なしの成功レスポンスまたは404エラー
     */
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

        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
