<?php

namespace Database\Seeders;

use App\Models\Book;
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

        $comments = [
            1 => [
                '残念ながら合いませんでした。',
                '期待と違いました。',
            ],
            2 => [
                '少し期待外れでした。',
                '内容が薄い印象。',
                'もう少し深掘りしてほしかった。',
            ],
            3 => [
                '普通でした。',
                '可もなく不可もなく。',
                '期待したほどではなかった。',
            ],
            4 => [
                'とても参考になりました。',
                '読みやすくておすすめです。',
                '期待通りの内容でした。',
            ],
            5 => [
                '素晴らしい本でした！',
                '人生が変わりました。',
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
            $reviewCount = random_int(2, 4);
            $reviewers = $users->random($reviewCount)->values();

            foreach ($reviewers as $reviewIndex => $reviewer) {
                $rating = 1 + (($bookIndex + $reviewIndex) % 5);
                $commentOptions = $comments[$rating];

                $reviewer->reviews()
                    ->create([
                        'book_id' => $book->getKey(),
                        'rating' => $rating,
                        'comment' => $commentOptions[
                            ($bookIndex + $reviewIndex) % count($commentOptions)
                        ],
                    ]);
            }
        }
    }
}
