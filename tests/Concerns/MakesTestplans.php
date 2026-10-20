<?php

namespace Tests\Concerns;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\Testplan;
use App\Models\User;

trait MakesTestplans
{
    protected function planPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Checkout flow',
            'description' => '<p>Describe it</p>',
            'categoryType' => Category::factory()->create()->id,
            'caseInformation' => 'Steps to test',
            'versionType' => 'beta',
            'versionNumber' => '1.2.3',
            'typeOfStatus' => 'New',
        ], $overrides);
    }

    /** Plan owned by $owner with an assignment row (and CSV column) for $assignee in $roleId. */
    protected function planWithAssignment(User $owner, User $assignee, int $roleId = 1, array $planOverrides = []): array
    {
        $plan = Testplan::factory()->create(array_merge(['created_by' => $owner->id], $planOverrides));
        $assign = $this->addAssignment($plan, $assignee, $roleId, $owner);

        return [$plan->fresh(), $assign];
    }

    protected function addAssignment(Testplan $plan, User $assignee, int $roleId, ?User $by = null): Assignment
    {
        $assign = Assignment::factory()->create([
            'testplan_id' => $plan->id,
            'assigned_by' => ($by ?? $plan->creator)->id,
            'user_id' => $assignee->id,
            'role_id' => $roleId,
            'role_name' => Assignment::roleNameForId($roleId),
            'field_name' => Assignment::fieldForRoleId($roleId),
        ]);

        $col = Assignment::dbFieldForRoleId($roleId);
        $ids = $plan->$col ? explode(',', $plan->$col) : [];
        $ids[] = $assignee->id;
        $plan->$col = implode(',', $ids);
        $plan->save();

        return $assign;
    }

    protected function assignmentBody(Assignment $a, array $extra = []): array
    {
        return array_merge([
            'assignId' => $a->testplan_id,
            'idAssign' => $a->role_id,
            'targetUserId' => $a->user_id,
        ], $extra);
    }
}
