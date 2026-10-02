<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * ログインユーザーがお気に入り登録した書籍一覧を表示します。
     *
     * @param  Request  $request  ログインユーザーを含むリクエスト
     * @return View お気に入り一覧画面
     */
    public function index(Request $request): View
    {
        $books = $request->user()
            ->favoriteBooks()
            ->orderByPivot('id', 'desc')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * 指定された書籍のお気に入り登録状態を切り替えます。
     *
     * @param  Request  $request  ログインユーザーを含むリクエスト
     * @param  Book  $book  お気に入り状態を切り替える書籍
     * @return RedirectResponse 元の画面へのリダイレクト
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $changes = $request->user()
            ->favoriteBooks()
            ->toggle($book->getKey());

        $message = $changes['attached'] !== []
            ? 'お気に入りに追加しました。'
            : 'お気に入りを解除しました。';

        return back()->with('success', $message);
    }
}
