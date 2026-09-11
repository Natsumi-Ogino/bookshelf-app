<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Policies\BookPolicy;
use App\Policies\ReviewPolicy;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    public function test_book_owner_can_update_and_delete_book(): void
    {
        $owner = new User;
        $owner->id = 1;

        $book = new Book;
        $book->user_id = 1;

        $policy = new BookPolicy;

        $this->assertTrue($policy->update($owner, $book));
        $this->assertTrue($policy->delete($owner, $book));
    }

    public function test_non_owner_cannot_update_or_delete_book(): void
    {
        $otherUser = new User;
        $otherUser->id = 2;

        $book = new Book;
        $book->user_id = 1;

        $policy = new BookPolicy;

        $this->assertFalse($policy->update($otherUser, $book));
        $this->assertFalse($policy->delete($otherUser, $book));
    }

    public function test_review_owner_can_update_and_delete_review(): void
    {
        $owner = new User;
        $owner->id = 1;

        $review = new Review;
        $review->user_id = 1;

        $policy = new ReviewPolicy;

        $this->assertTrue($policy->update($owner, $review));
        $this->assertTrue($policy->delete($owner, $review));
    }

    public function test_non_owner_cannot_update_or_delete_review(): void
    {
        $otherUser = new User;
        $otherUser->id = 2;

        $review = new Review;
        $review->user_id = 1;

        $policy = new ReviewPolicy;

        $this->assertFalse($policy->update($otherUser, $review));
        $this->assertFalse($policy->delete($otherUser, $review));
    }
}
