<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Testplan;
use App\Models\User;
use App\Rules\StorageFileUrl;
use App\Services\AlertService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends Controller
{
    private const GENERIC_ERROR = 'Something went wrong. Please try again.';

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['result' => 'error', 'message' => $message], $status);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->all();
        $sessionId = $user->id;

        $assignId = (int) ($data['assignId'] ?? 0);
        if (! $assignId) {
            return $this->error('Missing assignId', 400);
        }

        // UI (testplan/index.blade.php updateAssignment modal): level 0-100 slider,
        // whatStatus select pending|done|go back, goLive checkbox sent as 'yes'|'no'.
        $validator = Validator::make($data, [
            'idAssign' => ['nullable', 'integer', 'between:0,6'],
            'targetUserId' => ['nullable', 'integer'],
            'level' => ['nullable', 'integer', 'between:0,100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'whatStatus' => ['nullable', 'string', 'in:pending,done,go back'],
            'goLive' => ['nullable', 'in:yes,no,true,false,1,0,on,off'],
            'itemDataFile' => ['nullable', 'array', 'max:50'],
            'itemDataFile.*' => ['string', new StorageFileUrl],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'result' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $idAssign = (int) ($data['idAssign'] ?? 0);
        $userId = (int) ($data['targetUserId'] ?? 0);
        $levelInput = isset($data['level']) && $data['level'] !== '' ? (int) $data['level'] : null;
        $newDesc = trim((string) ($data['description'] ?? ''));
        $whatStatus = trim((string) ($data['whatStatus'] ?? ''));
        $goLive = isset($data['goLive']) && $data['goLive'] !== ''
            ? (filter_var($data['goLive'], FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no')
            : null;

        $itemUrls = $data['itemDataFile'] ?? [];
        $cleanUrls = array_values(array_unique(array_filter($itemUrls)));

        try {
            $result = DB::transaction(function () use ($user, $sessionId, $assignId, $idAssign, $userId, $levelInput, $newDesc, $whatStatus, $goLive, $cleanUrls, $data) {
                // Lock order: testplan first, then assignment rows.
                $testplan = Testplan::lockForUpdate()->find($assignId);
                if (! $testplan) {
                    return $this->error('Assignment not found', 404);
                }

                $assign = Assignment::where('testplan_id', $assignId)
                    ->where('user_id', $userId)
                    ->where('role_id', $idAssign)
                    ->lockForUpdate()
                    ->first();

                if (! $assign) {
                    return $this->error('Assignment not found', 404);
                }

                if (! $user->can('updateAssignment', [$testplan, $assign])) {
                    return $this->error('Access Denied', 403);
                }

                // A missing level keeps the stored value (never persists a negative "unset").
                $newLevel = $levelInput ?? (int) $assign->level;
                $assign->file_info = $cleanUrls;

                $currentWhatStatus = ($newLevel === 100) ? 'done' : (($whatStatus === 'go back') ? 'go back' : 'pending');

                // Live Tester (role 6) completing -> mark testplan as Completed
                if ($assign->role_id === 6) {
                    $testplan->main_status = ($currentWhatStatus === 'done') ? 'Completed' : 'Open';
                    $testplan->save();
                }

                // "Go back" from non-dev stage -> reset dev and intermediate stages
                if ($idAssign !== 1 && $whatStatus === 'go back') {
                    Assignment::where('testplan_id', $assignId)
                        ->where('role_id', 1)
                        ->update(['level' => 0, 'what_status' => 'pending', 'status' => 'pending']);

                    Assignment::where('testplan_id', $assignId)
                        ->where('role_id', '>', 1)
                        ->where('role_id', '<', $idAssign)
                        ->update(['level' => 0, 'go_live' => 'no', 'what_status' => 'go back', 'status' => 'pending']);
                }

                // Dev completing -> reset "go back" stages to pending
                if ($idAssign === 1 && $whatStatus === 'done') {
                    Assignment::where('testplan_id', $assignId)
                        ->where('role_id', '>', 1)
                        ->where('what_status', 'go back')
                        ->update(['level' => 0, 'what_status' => 'pending', 'status' => 'pending']);
                }

                // "Go Live" flag -> Live Tester assignment for the creator + mark intermediates done
                if ($goLive === 'yes') {
                    $creatorId = (int) $testplan->created_by;

                    $existingLive = Assignment::where('testplan_id', $assignId)
                        ->where('role_id', 6)
                        ->where('user_id', $creatorId)
                        ->lockForUpdate()
                        ->first();

                    if (! $existingLive) {
                        Assignment::create([
                            'testplan_id' => $assignId,
                            'assigned_by' => $sessionId,
                            'user_id' => $creatorId,
                            'role_id' => 6,
                            'role_name' => 'Live Tester',
                            'level' => 0,
                            'status' => 'pending',
                            'what_status' => 'pending',
                            'field_name' => 'liveTesterId',
                        ]);

                        $ids = $testplan->live_tester_ids ? explode(',', $testplan->live_tester_ids) : [];
                        $ids[] = (string) $creatorId;
                        $testplan->live_tester_ids = implode(',', array_values(array_unique(array_filter($ids))));
                        $testplan->save();
                    }

                    Assignment::where('testplan_id', $assignId)
                        ->where('role_id', '>', 1)
                        ->where('role_id', '<', 6)
                        ->whereIn('what_status', ['go back', 'pending'])
                        ->update(['level' => 100, 'what_status' => 'done', 'status' => 'done']);
                }

                $assign->level = $newLevel;
                if ($goLive !== null) {
                    $assign->go_live = $goLive;
                }
                $assign->description = $newDesc;
                $assign->what_status = $currentWhatStatus;
                $assign->status = ($newLevel === 100) ? 'done' : 'pending';
                $assign->save();

                ActivityLog::create([
                    'user_id' => $sessionId,
                    'action' => "Updated Assignment #{$assignId}",
                    'data' => $data,
                    'description' => "Updated progress to {$newLevel}%. Attachments: ".count($cleanUrls),
                ]);

                return compact('testplan', 'assign', 'newLevel', 'currentWhatStatus');
            });
        } catch (\Throwable $e) {
            Log::error('Assignment update failed', ['exception' => $e]);

            return $this->error(self::GENERIC_ERROR, 500);
        }

        if ($result instanceof JsonResponse) {
            return $result;
        }

        ['testplan' => $testplan, 'assign' => $assign, 'newLevel' => $newLevel, 'currentWhatStatus' => $currentWhatStatus] = $result;

        // Alerts after commit; a failure here must not turn saved data into a 500.
        try {
            $alertService = app(AlertService::class);

            if ($newLevel === 100 && $currentWhatStatus === 'done') {
                if ($assign->role_id === 6 && $testplan->main_status === 'Completed') {
                    $alertService->notifyAllCompleted($testplan, $assign, $user);
                } else {
                    $alertService->notifyProgressCompleted($testplan, $assign, $user);
                }
            }

            if ($whatStatus === 'go back' && $idAssign !== 1) {
                $alertService->notifyProgressReverted($testplan, $assign, $user, $newDesc);
            }
        } catch (\Throwable $e) {
            Log::error('Assignment update alert failed', ['exception' => $e, 'testplan_id' => $assignId]);
        }

        return response()->json(['result' => 'success', 'message' => 'Assignment updated successfully']);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->all();
        $sessionId = $user->id;

        $assignId = (int) ($data['assignId'] ?? 0);
        $idAssign = (int) ($data['idAssign'] ?? 0);
        $uid = (int) ($data['targetUserId'] ?? 0);

        if (! $assignId || ! $idAssign || ! $uid) {
            return $this->error('Missing required parameters', 400);
        }

        $testplan = Testplan::find($assignId);
        if (! $testplan) {
            return $this->error('Testplan not found', 404);
        }

        if (! $user->can('manage', $testplan)) {
            return $this->error('Permission denied', 403);
        }

        $field = Assignment::fieldForRoleId($idAssign);
        $role = Assignment::roleNameForId($idAssign);

        if (! $field || ! $role) {
            return $this->error("Invalid idAssign value: {$idAssign}", 400);
        }

        if (! User::whereKey($uid)->exists()) {
            return $this->error("Invalid user ID {$uid}", 400);
        }

        try {
            $result = DB::transaction(function () use ($assignId, $idAssign, $uid, $sessionId, $field, $role, $user, $data) {
                $testplan = Testplan::lockForUpdate()->find($assignId);
                if (! $testplan) {
                    return $this->error('Testplan not found', 404);
                }

                $exists = Assignment::where('testplan_id', $assignId)
                    ->where('role_id', $idAssign)
                    ->where('user_id', $uid)
                    ->exists();

                if ($exists) {
                    return $this->error("User {$uid} is already assigned as {$role}", 409);
                }

                $assign = Assignment::create([
                    'testplan_id' => $assignId,
                    'assigned_by' => $sessionId,
                    'user_id' => $uid,
                    'role_id' => $idAssign,
                    'role_name' => $role,
                    'level' => 0,
                    'status' => 'pending',
                    'what_status' => 'pending',
                    'field_name' => $field,
                ]);

                $dbField = Assignment::dbFieldForRoleId($idAssign);
                if ($dbField) {
                    $currentIds = ! empty($testplan->$dbField) ? explode(',', $testplan->$dbField) : [];
                    $currentIds[] = (string) $uid;
                    $testplan->$dbField = implode(',', array_values(array_unique(array_filter($currentIds))));
                    $testplan->save();
                }

                ActivityLog::create([
                    'user_id' => $sessionId,
                    'action' => "Added New Assignment: User ID {$uid}",
                    'data' => $data,
                    'description' => "User ({$user->permission_level}) assigned user {$uid} as {$role} to testplan #{$assignId}.",
                ]);

                return ['testplan' => $testplan, 'assign' => $assign];
            });
        } catch (UniqueConstraintViolationException $e) {
            return $this->error("User {$uid} is already assigned as {$role}", 409);
        } catch (\Throwable $e) {
            Log::error('Assignment store failed', ['exception' => $e]);

            return $this->error(self::GENERIC_ERROR, 500);
        }

        if ($result instanceof JsonResponse) {
            return $result;
        }

        try {
            app(AlertService::class)->notifyAssignmentAdded($result['testplan'], $role, $uid, $user);
        } catch (\Throwable $e) {
            Log::error('Assignment store alert failed', ['exception' => $e, 'testplan_id' => $assignId]);
        }

        return response()->json([
            'result' => 'success',
            'message' => 'Assignment added successfully',
            'id' => $result['assign']->id,
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $data = $request->all();
        $sessionId = $user->id;

        $assignId = (int) ($data['assignId'] ?? 0);
        $userId = (int) ($data['targetUserId'] ?? 0);
        $idAssign = (int) ($data['idAssign'] ?? 0);

        if (! $assignId || ! $userId) {
            return $this->error('Missing assignId or userId', 400);
        }

        try {
            $result = DB::transaction(function () use ($assignId, $userId, $idAssign, $user, $sessionId) {
                $testplan = Testplan::lockForUpdate()->find($assignId);

                $assign = $testplan
                    ? Assignment::where('testplan_id', $assignId)
                        ->where('user_id', $userId)
                        ->where('role_id', $idAssign)
                        ->lockForUpdate()
                        ->first()
                    : null;

                if (! $assign) {
                    return $this->error('Assignment not found', 404);
                }

                if (! $user->can('manage', $testplan)) {
                    return $this->error('Permission denied', 403);
                }

                $logData = $assign->toArray();
                $assign->delete();

                $dbField = Assignment::dbFieldForRoleId($idAssign);
                if ($dbField && ! empty($testplan->$dbField)) {
                    $currentIds = array_values(array_filter(
                        explode(',', $testplan->$dbField),
                        fn ($id) => (int) $id !== $userId
                    ));
                    $testplan->$dbField = ! empty($currentIds) ? implode(',', $currentIds) : null;
                    $testplan->save();
                }

                ActivityLog::create([
                    'user_id' => $sessionId,
                    'action' => "Deleted Assignment: User ID {$userId} from Plan #{$assignId}",
                    'data' => $logData,
                    'description' => "User ({$user->permission_level}) removed user {$userId} from role ID {$idAssign} on testplan {$assignId}.",
                ]);

                return null;
            });
        } catch (\Throwable $e) {
            Log::error('Assignment destroy failed', ['exception' => $e]);

            return $this->error(self::GENERIC_ERROR, 500);
        }

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json(['result' => 'success', 'message' => 'Assignment deleted successfully']);
    }
}
