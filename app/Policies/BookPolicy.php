<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * ユーザーが書籍一覧を閲覧できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @return bool 閲覧可能なため常にtrue
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが指定された書籍を閲覧できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Book  $book  閲覧対象の書籍
     * @return bool 閲覧可能なため常にtrue
     */
    public function view(User $user, Book $book): bool
    {
        return true;
    }

    /**
     * ユーザーが書籍を登録できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @return bool 登録可能なため常にtrue
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * ユーザーが指定された書籍を更新できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Book  $book  更新対象の書籍
     * @return bool ユーザーが書籍の所有者の場合はtrue
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * ユーザーが指定された書籍を削除できるか判定します。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  Book  $book  削除対象の書籍
     * @return bool ユーザーが書籍の所有者の場合はtrue
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
