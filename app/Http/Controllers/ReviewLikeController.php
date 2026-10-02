<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
    /**
     * 他ユーザーのレビューに対するいいね状態を切り替えます。
     *
     * @param  Request  $request  ログインユーザーを含むリクエスト
     * @param  Review  $review  いいね状態を切り替えるレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function toggle(Request $request, Review $review): RedirectResponse
    {
        if ((int) $review->user_id === (int) $request->user()->getKey()) {
            abort(403);
        }

        $changes = $request->user()
            ->likedReviews()
            ->toggle($review->getKey());

        $message = $changes['attached'] !== []
            ? 'レビューにいいねしました。'
            : 'レビューのいいねを解除しました。';

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', $message);
    }
}
