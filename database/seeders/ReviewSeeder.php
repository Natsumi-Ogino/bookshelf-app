<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ReviewSeeder extends Seeder
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

        $reviewerIndexesByBook = [
            [0, 1, 2],
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 0],
            [4, 0, 1],
            [0, 2, 4],
            [1, 3, 0],
            [2, 4, 1],
            [3, 0, 2],
            [4, 1, 3],
            [0, 4],
        ];

        $comments = [
            3 => [
                '普通でした。',
                '良い点も気になる点もありました。',
                '期待したほどではなかったです。',
            ],
            4 => [
                'とても参考になりました。',
                '読みやすくておすすめです。',
                '期待どおりの内容でした。',
            ],
            5 => [
                '素晴らしい本でした！',
                '考え方が変わりました。',
                '何度も読み返しています。',
            ],
        ];

        $users = User::query()
            ->oldest('id')
            ->limit(5)
            ->get();

        if ($users->count() !== 5) {
            throw new RuntimeException('ReviewSeederの実行には5人のユーザーが必要です。');
        }

        $books = Book::query()
            ->whereIn('isbn', $isbnOrder)
            ->get()
            ->keyBy('isbn');

        if ($books->count() !== count($isbnOrder)) {
            throw new RuntimeException('ReviewSeederの実行には指定された11冊の書籍が必要です。');
        }

        foreach ($isbnOrder as $bookIndex => $isbn) {
            $book = $books->get($isbn);

            foreach ($reviewerIndexesByBook[$bookIndex] as $reviewIndex => $userIndex) {
                $rating = 3 + (($bookIndex + $reviewIndex) % 3);
                $commentOptions = $comments[$rating];

                $review = new Review([
                    'rating' => $rating,
                    'comment' => $commentOptions[
                        ($bookIndex + $reviewIndex) % count($commentOptions)
                    ],
                ]);

                $review->user()->associate($users->get($userIndex));
                $review->book()->associate($book);
                $review->save();
            }
        }
    }
}
