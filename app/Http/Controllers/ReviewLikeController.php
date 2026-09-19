<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
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
