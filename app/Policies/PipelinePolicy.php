<?php

namespace App\Policies;

use App\Models\Pipeline;
use App\Models\User;

class PipelinePolicy
{
    public function view(User $user, Pipeline $pipeline): bool
    {
        if (in_array($user->role, ['hod', 'admin'])) {
            return true;
        }

        if ($user->role === 'manager') {
            return $pipeline->salesperson->team_id === $user->team_id;
        }

        return $pipeline->salesperson_id === $user->id;
    }

    public function update(User $user, Pipeline $pipeline): bool
    {
        if ($pipeline->is_locked && ! in_array($user->role, ['manager', 'admin'])) {
            return false;
        }

        return $this->view($user, $pipeline);
    }

    public function delete(User $user, Pipeline $pipeline): bool
    {
        return $user->role === 'admin';
    }
}