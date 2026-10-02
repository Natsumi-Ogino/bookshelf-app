<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * ユーザーがレビュー一覧を閲覧できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @return bool 閲覧可能なため常にtrue
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが指定されたレビューを閲覧できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Review  $review  閲覧対象のレビュー
     * @return bool 閲覧可能なため常にtrue
     */
    public function view(User $user, Review $review): bool
    {
        return true;
    }

    /**
     * ユーザーがレビューを投稿できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @return bool 投稿可能なため常にtrue
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが指定されたレビューを更新できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Review  $review  更新対象のレビュー
     * @return bool ユーザーがレビューの投稿者の場合はtrue
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * ユーザーが指定されたレビューを削除できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Review  $review  削除対象のレビュー
     * @return bool ユーザーがレビューの投稿者の場合はtrue
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
