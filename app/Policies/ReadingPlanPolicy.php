<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->is($readingPlan->user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->is($readingPlan->user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->is($readingPlan->user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReadingPlan $readingPlan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReadingPlan $readingPlan): bool
    {
        return false;
    }
}
