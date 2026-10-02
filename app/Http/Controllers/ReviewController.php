<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    /**
     * ログインユーザーのレビューを指定された書籍へ投稿します。
     *
     * @param  StoreReviewRequest  $request  検証済みの評価とコメントを含むリクエスト
     * @param  Book  $book  レビュー対象の書籍
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('create', Review::class);

        $hasReviewed = $book->reviews()
            ->where('user_id', $request->user()->getKey())
            ->exists();

        if ($hasReviewed) {
            return $this->duplicateReviewResponse($book);
        }

        $review = new Review($request->validated());
        $review->user()->associate($request->user());
        $review->book()->associate($book);

        try {
            $review->save();
        } catch (UniqueConstraintViolationException) {
            return $this->duplicateReviewResponse($book);
        }

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    /**
     * 所有者にレビュー編集画面を表示します。
     *
     * @param  Review  $review  編集対象のレビュー
     * @return View レビュー編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    /**
     * 所有者のレビュー内容を更新します。
     *
     * @param  UpdateReviewRequest  $request  検証済みの評価とコメントを含むリクエスト
     * @param  Review  $review  更新対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    /**
     * 所有者のレビューを削除します。
     *
     * @param  Review  $review  削除対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;
        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }

    /**
     * 同じ書籍への重複レビューを日本語エラー付きで差し戻します。
     *
     * @param  Book  $book  重複レビューの対象書籍
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    private function duplicateReviewResponse(Book $book): RedirectResponse
    {
        return redirect()
            ->route('books.show', $book)
            ->withErrors([
                'review' => 'この書籍には既にレビューを投稿しています。',
            ])
            ->withInput();
    }
}
