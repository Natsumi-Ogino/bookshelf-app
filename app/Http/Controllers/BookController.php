<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Services\BookSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 検索・ジャンル・並び順の条件を反映した書籍一覧を表示します。
     *
     * @param  IndexBookRequest  $request  検証済みの一覧表示条件を含むリクエスト
     * @return View 書籍一覧画面
     */
    public function index(
        IndexBookRequest $request,
        BookSearchService $bookSearch
    ): View {
        $filters = $request->validated();

        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        $books = $bookSearch
            ->query($filters)
            ->paginate(10)
            ->appends($filters);

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍登録画面と選択可能なジャンル一覧を表示します。
     *
     * @return View 書籍登録画面
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
     * ログインユーザーの書籍を登録し、ジャンルを紐づけます。
     *
     * @param  StoreBookRequest  $request  検証済みの書籍情報を含むリクエスト
     * @return RedirectResponse 登録した書籍の詳細画面へのリダイレクト
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
     * 指定された書籍とジャンル・レビュー・評価情報を表示します。
     *
     * @param  Book  $book  表示対象の書籍
     * @return View 書籍詳細画面
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
     * 所有者に書籍編集画面と選択可能なジャンル一覧を表示します。
     *
     * @param  Book  $book  編集対象の書籍
     * @return View 書籍編集画面
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
     * 所有者の書籍情報とジャンルの紐づけを更新します。
     *
     * @param  UpdateBookRequest  $request  検証済みの書籍情報を含むリクエスト
     * @param  Book  $book  更新対象の書籍
     * @return RedirectResponse 更新した書籍の詳細画面へのリダイレクト
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
     * 所有者の書籍を削除します。
     *
     * @param  Book  $book  削除対象の書籍
     * @return RedirectResponse 書籍一覧画面へのリダイレクト
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
