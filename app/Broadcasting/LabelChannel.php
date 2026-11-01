<?php

namespace App\Broadcasting;

use App\Models\Label;
use App\Models\User;

class LabelChannel
{
    /**
     * Only the members of an active label may listen to its updates.
     */
    public function join(User $user, Label $label): bool
    {
        if ($label->isSuspended()) {
            return false;
        }

        return $label->hasMember($user);
    }
}
