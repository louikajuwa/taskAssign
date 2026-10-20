<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Testplan;
use App\Models\User;

class TestplanPolicy
{
    /** Edit/delete the plan, add/remove assignments: creator or admin. */
    public function manage(User $user, Testplan $testplan): bool
    {
        return $user->isAdmin() || (int) $testplan->created_by === (int) $user->id;
    }

    /** View a plan's comment threads: creator, admin, anyone assigned on the plan, or a listed developer. */
    public function participate(User $user, Testplan $testplan): bool
    {
        if ($this->manage($user, $testplan) || $this->isListedDeveloper($user, $testplan)) {
            return true;
        }

        return Assignment::where('testplan_id', $testplan->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /** Post a comment on an assignment's thread. */
    public function comment(User $user, Testplan $testplan, Assignment $assign): bool
    {
        return (int) $assign->user_id === (int) $user->id
            || $this->manage($user, $testplan)
            || ($user->permission_level === 'Developer' && $this->isListedDeveloper($user, $testplan));
    }

    /** Update an assignment's progress: creator, the assigned user, or admin. */
    public function updateAssignment(User $user, Testplan $testplan, Assignment $assign): bool
    {
        return (int) $assign->user_id === (int) $user->id || $this->manage($user, $testplan);
    }

    private function isListedDeveloper(User $user, Testplan $testplan): bool
    {
        $ids = $testplan->assign_to_dev_ids
            ? array_map('intval', explode(',', $testplan->assign_to_dev_ids))
            : [];

        return in_array((int) $user->id, $ids, true);
    }
}
