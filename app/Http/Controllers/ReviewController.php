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

    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;
        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }

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
