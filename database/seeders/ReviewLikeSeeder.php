<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()
            ->oldest('id')
            ->limit(5)
            ->get();

        if ($users->count() !== 5) {
            throw new RuntimeException('ReviewLikeSeederの実行には5人のユーザーが必要です。');
        }

        $reviews = Review::query()
            ->oldest('id')
            ->get();

        if ($reviews->count() !== 32) {
            throw new RuntimeException('ReviewLikeSeederの実行には32件のレビューが必要です。');
        }

        foreach ($reviews as $reviewIndex => $review) {
            $likeCount = $reviewIndex % 4;

            if ($likeCount === 0) {
                continue;
            }

            $eligibleUsers = $users
                ->reject(
                    fn (User $user): bool => (int) $user->getKey() === (int) $review->user_id
                )
                ->values();

            $startIndex = $reviewIndex % $eligibleUsers->count();

            $likeUserIds = array_map(
                fn (int $offset): int => $eligibleUsers
                    ->get(($startIndex + $offset) % $eligibleUsers->count())
                    ->getKey(),
                range(0, $likeCount - 1)
            );

            $review->likedByUsers()->syncWithoutDetaching($likeUserIds);
        }
    }
}
