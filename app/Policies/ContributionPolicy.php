<?php

namespace App\Policies;

use App\Models\Contribution;
use App\Models\User;

class ContributionPolicy
{
    /**
     * A donor may only change their own contribution, and only while it is pending.
     */
    public function update(User $user, Contribution $contribution): bool
    {
        if ($contribution->user_id !== $user->id) {
            return false;
        }

        return $contribution->isPending();
    }

    public function delete(User $user, Contribution $contribution): bool
    {
        return $this->update($user, $contribution);
    }
}
