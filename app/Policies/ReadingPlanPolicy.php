<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * ユーザーが読書計画一覧を閲覧できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @return bool 閲覧可能なため常にtrue
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが指定された読書計画を閲覧できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  ReadingPlan  $readingPlan  閲覧対象の読書計画
     * @return bool ユーザーが読書計画の所有者の場合はtrue
     */
    public function view(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->is($readingPlan->user);
    }

    /**
     * ユーザーが読書計画を作成できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @return bool 作成可能なため常にtrue
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが指定された読書計画を更新できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  ReadingPlan  $readingPlan  更新対象の読書計画
     * @return bool ユーザーが読書計画の所有者の場合はtrue
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->is($readingPlan->user);
    }

    /**
     * ユーザーが指定された読書計画を削除できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  ReadingPlan  $readingPlan  削除対象の読書計画
     * @return bool ユーザーが読書計画の所有者の場合はtrue
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->is($readingPlan->user);
    }

    /**
     * ユーザーが削除済みの読書計画を復元できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  ReadingPlan  $readingPlan  復元対象の読書計画
     * @return bool 復元を許可しないため常にfalse
     */
    public function restore(User $user, ReadingPlan $readingPlan): bool
    {
        return false;
    }

    /**
     * ユーザーが読書計画を完全削除できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  ReadingPlan  $readingPlan  完全削除対象の読書計画
     * @return bool 完全削除を許可しないため常にfalse
     */
    public function forceDelete(User $user, ReadingPlan $readingPlan): bool
    {
        return false;
    }
}
