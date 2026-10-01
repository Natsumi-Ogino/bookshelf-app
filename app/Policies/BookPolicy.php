<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /** ユーザーが書籍一覧を閲覧できるか判定します。 */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** ユーザーが指定された書籍を閲覧できるか判定します。 */
    public function view(User $user, Book $book): bool
    {
        return true;
    }

    /** ユーザーが書籍を登録できるか判定します。 */
    public function create(User $user): bool
    {
        return true;
    }

    /** ユーザーが指定された書籍を更新できるか判定します。 */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /** ユーザーが指定された書籍を削除できるか判定します。 */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
