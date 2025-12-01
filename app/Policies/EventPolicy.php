<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Decision makers can only view events they're assigned to
        return $user->isAssignedTo($event->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can trigger calculation for the event.
     */
    public function calculate(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Event leaders can trigger calculation
        return $user->isLeaderOf($event->id);
    }

    /**
     * Determine whether the user can modify Borda settings for the event.
     */
    public function modifyBordaSettings(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Event leaders can modify Borda settings
        return $user->isLeaderOf($event->id);
    }
}
