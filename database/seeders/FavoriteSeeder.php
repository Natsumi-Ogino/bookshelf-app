<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $isbnOrder = [
            '9784101010014',
            '9784422100524',
            '9784873115658',
            '9784863940246',
            '9784101010021',
            '9784309226712',
            '9784048930598',
            '9784478025819',
            '9784163902302',
            '9784822289607',
            '9784822251468',
        ];

        $favoriteBookIndexesByUser = [
            [0, 1, 2],
            [2, 3, 4, 5],
            [4, 5, 6, 7, 8],
            [6, 7, 8],
            [8, 9, 10, 0],
        ];

        $users = User::query()
            ->oldest('id')
            ->limit(5)
            ->get();

        if ($users->count() !== 5) {
            throw new RuntimeException('FavoriteSeederの実行には5人のユーザーが必要です。');
        }

        $books = Book::query()
            ->whereIn('isbn', $isbnOrder)
            ->get()
            ->keyBy('isbn');

        if ($books->count() !== count($isbnOrder)) {
            throw new RuntimeException('FavoriteSeederの実行には指定された11冊の書籍が必要です。');
        }

        foreach ($users as $userIndex => $user) {
            $bookIds = array_map(
                fn (int $bookIndex): int => $books
                    ->get($isbnOrder[$bookIndex])
                    ->getKey(),
                $favoriteBookIndexesByUser[$userIndex]
            );

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
