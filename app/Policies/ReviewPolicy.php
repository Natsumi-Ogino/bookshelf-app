<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /** ユーザーがレビュー一覧を閲覧できるか判定します。 */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** ユーザーが指定されたレビューを閲覧できるか判定します。 */
    public function view(User $user, Review $review): bool
    {
        return true;
    }

    /** ユーザーがレビューを投稿できるか判定します。 */
    public function create(User $user): bool
    {
        return true;
    }

    /** ユーザーが指定されたレビューを更新できるか判定します。 */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /** ユーザーが指定されたレビューを削除できるか判定します。 */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
