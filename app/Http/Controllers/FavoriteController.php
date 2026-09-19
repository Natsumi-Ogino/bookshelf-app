<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $books = $request->user()
            ->favoriteBooks()
            ->orderByPivot('id', 'desc')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

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
