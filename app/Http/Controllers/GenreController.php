<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * 書籍数を含むジャンル一覧を表示します。
     *
     * @return View ジャンル一覧画面
     */
    public function index(): View
    {
        $genres = Genre::query()
            ->withCount('books')
            ->orderBy('id')
            ->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面を表示します。
     *
     * @return View ジャンル登録画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * 検証済みのジャンルを登録します。
     *
     * @param  StoreGenreRequest  $request  検証済みのジャンル名を含むリクエスト
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        Genre::query()->create($request->validated());

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを登録しました。');
    }

    /**
     * 指定されたジャンルと紐づく書籍一覧を表示します。
     *
     * @param  Genre  $genre  表示対象のジャンル
     * @return View ジャンル詳細画面
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->with('genres')
            ->orderBy('books.id')
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * 指定されたジャンルの編集画面を表示します。
     *
     * @param  Genre  $genre  編集対象のジャンル
     * @return View ジャンル編集画面
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * 指定されたジャンル名を更新します。
     *
     * @param  UpdateGenreRequest  $request  検証済みのジャンル名を含むリクエスト
     * @param  Genre  $genre  更新対象のジャンル
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを更新しました。');
    }

    /**
     * 書籍が紐づいていないジャンルを削除します。
     *
     * @param  Genre  $genre  削除対象のジャンル
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        if ($genre->books()->exists()) {
            return redirect()
                ->route('genres.index')
                ->with('error', '書籍が紐づいているため、このジャンルは削除できません。');
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました。');
    }
}
