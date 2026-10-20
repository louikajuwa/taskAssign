<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTestplanRequest;
use App\Http\Requests\UpdateTestplanRequest;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Testplan;
use App\Models\User;
use App\Services\AlertService;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestplanController extends Controller
{
    private const GENERIC_ERROR = 'Something went wrong. Please try again.';

    private const ID_FIELDS = [
        'assign_to_dev_ids', 'generic_tester_ids', 'sandbox_tester_ids',
        'alpha_tester_ids', 'beta_tester_ids', 'live_tester_ids',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        return view('testplan.index', [
            'userId' => $user->id,
            'userRole' => $user->permission_level,
            'username' => $user->name,
            'userEmail' => $user->email,
            'allowCreation' => $user->allow_creation ? 'yes' : 'no',
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function list(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();
        $sessionId = $user->id;

        $page = max(1, (int) ($request->input('currentPage', 1)));
        $limit = min(100, max(1, (int) ($request->input('limit', 10))));
        $searchKey = trim((string) $request->input('searchKey', 'all'));
        $searchQuery = trim((string) $request->input('searchQuery', ''));
        $categoryFilter = trim((string) $request->input('categoryFilter', ''));

        $query = Testplan::query();

        if (! $isAdmin) {
            $query->where(function ($q) use ($sessionId) {
                $q->where('created_by', $sessionId);
                foreach (self::ID_FIELDS as $field) {
                    $q->orWhereRaw("FIND_IN_SET(?, {$field})", [$sessionId]);
                }
            });
        }

        if ($searchKey === 'MyCreated') {
            $query->where('created_by', $sessionId);
        } elseif ($searchKey === 'AssignToMe') {
            $query->where(function ($q) use ($sessionId) {
                foreach (self::ID_FIELDS as $field) {
                    $q->orWhereRaw("FIND_IN_SET(?, {$field})", [$sessionId]);
                }
            });
        } elseif ($searchKey !== 'all' && ! empty($searchKey)) {
            $query->where('main_status', $searchKey);
        }

        if (! empty($searchQuery)) {
            $query->where('title', 'like', '%'.$searchQuery.'%');
        }

        if (! empty($categoryFilter)) {
            $query->where('category_id', $categoryFilter);
        }

        $totalRecords = $query->count();
        $plans = $query->with(['assignments.assignee', 'creator', 'category'])
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $results = [];

        foreach ($plans as $plan) {
            $assignmentsArray = [];

            foreach ($plan->assignments as $a) {
                $key = $a->role_id.'_'.$a->user_id.'_'.$a->field_name;
                if (! isset($assignmentsArray[$key])) {
                    $item = $a->toArray();
                    $assignee = $a->assignee;
                    $item['userEmail'] = $assignee->email ?? null;
                    $item['userName'] = $assignee->name ?? null;
                    // Map back to legacy field names for frontend compatibility
                    $item['idAssign'] = $a->role_id;
                    $item['idUser'] = $a->user_id;
                    $item['whichId'] = $a->testplan_id;
                    $item['whatStatus'] = $a->what_status;
                    $item['fileInfo'] = $a->file_info;
                    $item['whichPart'] = $a->field_name;
                    $item['comment'] = $a->comment_thread_id;
                    $item['goLive'] = $a->go_live;
                    $assignmentsArray[$key] = $item;
                }
            }

            $planArray = $plan->toArray();
            $planArray['assignments'] = array_values($assignmentsArray);
            $planArray['creatorEmail'] = $plan->creator->email ?? null;
            // Unix timestamp (seconds) for the frontend date formatter — easy to format/edit client-side
            $planArray['created'] = $plan->created_at?->timestamp;
            $planArray['timestamp'] = $plan->created_at?->timestamp;
            // Legacy field mappings for frontend
            $planArray['mainstatus'] = $plan->main_status;
            $planArray['categoryType'] = $plan->category_id;
            $planArray['caseInformation'] = $plan->case_information;
            $planArray['versionType'] = $plan->version_type;
            $planArray['versionNumber'] = $plan->version_number;
            $planArray['typeOfStatus'] = $plan->type_of_status;
            $planArray['itemDataFile'] = $plan->item_data_file ? json_encode($plan->item_data_file) : '[]';
            $planArray['whoCreated'] = $plan->created_by;
            $planArray['asignToDeVId'] = $plan->assign_to_dev_ids;
            $planArray['generecTesterId'] = $plan->generic_tester_ids;
            $planArray['sandboxTesterId'] = $plan->sandbox_tester_ids;
            $planArray['alphaTesterId'] = $plan->alpha_tester_ids;
            $planArray['betaTesterId'] = $plan->beta_tester_ids;
            $planArray['liveTesterId'] = $plan->live_tester_ids;

            $results[] = $planArray;
        }

        return response()->json([
            'result' => 'success',
            'data' => $results,
            'pagination' => [
                'totalRecords' => $totalRecords,
                'totalPages' => ceil($totalRecords / $limit),
                'currentPage' => $page,
                'limit' => $limit,
            ],
        ]);
    }

    public function store(StoreTestplanRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $roleFields = [];
        foreach (Assignment::ROLE_MAP as $field => $info) {
            $roleFields[$field] = $info;
        }

        // Normalize tester id arrays, reject duplicates, and verify all users exist in one query.
        $allIds = [];
        foreach ($roleFields as $field => $info) {
            $ids = array_values(array_filter(array_map('intval', $data[$field] ?? [])));
            if (count($ids) !== count(array_unique($ids))) {
                return response()->json(['result' => 'error', 'message' => "Field {$field} contains duplicate IDs"], 400);
            }
            $data[$field] = $ids;
            $allIds = array_merge($allIds, $ids);
        }

        $allIds = array_values(array_unique($allIds));
        if ($allIds) {
            $found = User::whereIn('id', $allIds)->pluck('id')->map(fn ($i) => (int) $i)->all();
            foreach ($roleFields as $field => $info) {
                foreach ($data[$field] as $id) {
                    if (! in_array($id, $found, true)) {
                        return response()->json(['result' => 'error', 'message' => "Invalid user ID {$id} in field {$field}"], 400);
                    }
                }
            }
        }

        $alerts = [];

        try {
            $testplan = DB::transaction(function () use ($data, $roleFields, $user, &$alerts) {
                $data['description'] = HtmlSanitizer::clean($data['description']);

                $testplan = Testplan::create([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'category_id' => $data['categoryType'],
                    'case_information' => $data['caseInformation'],
                    'version_type' => $data['versionType'],
                    'version_number' => $data['versionNumber'] ?? null,
                    'type_of_status' => $data['typeOfStatus'] ?? null,
                    'priority' => $data['priority'] ?? null,
                    'main_status' => 'Open',
                    'item_data_file' => $data['itemDataFile'] ?? [],
                    'assign_to_dev_ids' => $data['asignToDeVId'] ? implode(',', $data['asignToDeVId']) : null,
                    'generic_tester_ids' => $data['generecTesterId'] ? implode(',', $data['generecTesterId']) : null,
                    'sandbox_tester_ids' => $data['sandboxTesterId'] ? implode(',', $data['sandboxTesterId']) : null,
                    'alpha_tester_ids' => $data['alphaTesterId'] ? implode(',', $data['alphaTesterId']) : null,
                    'beta_tester_ids' => $data['betaTesterId'] ? implode(',', $data['betaTesterId']) : null,
                    'live_tester_ids' => $data['liveTesterId'] ? implode(',', $data['liveTesterId']) : null,
                    'created_by' => $user->id,
                ]);

                foreach ($roleFields as $field => $info) {
                    foreach ($data[$field] as $uid) {
                        Assignment::create([
                            'testplan_id' => $testplan->id,
                            'assigned_by' => $user->id,
                            'user_id' => $uid,
                            'role_id' => $info['id'],
                            'role_name' => $info['role'],
                            'level' => 0,
                            'status' => 'pending',
                            'what_status' => 'pending',
                            'field_name' => $field,
                        ]);

                        $alerts[] = [$info['role'], $uid];
                    }
                }

                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'Created Testplan: '.$testplan->title,
                    'data' => $data,
                    'description' => "New test plan created with ID: {$testplan->id} and version: {$testplan->version_number}",
                ]);

                return $testplan;
            });
        } catch (\Throwable $e) {
            Log::error('Testplan store failed', ['exception' => $e]);

            return response()->json(['result' => 'error', 'message' => self::GENERIC_ERROR], 500);
        }

        // Alerts are dispatched only after the transaction committed; failures never affect the saved data.
        try {
            $alertService = app(AlertService::class);
            foreach ($alerts as [$role, $uid]) {
                $alertService->notifyAssignmentAdded($testplan, $role, $uid, $user);
            }
        } catch (\Throwable $e) {
            Log::error('Testplan alert dispatch failed', ['exception' => $e, 'testplan_id' => $testplan->id]);
        }

        return response()->json([
            'result' => 'success',
            'message' => 'Testplan created successfully',
            'id' => $testplan->id,
        ]);
    }

    public function update(UpdateTestplanRequest $request, $id)
    {
        $user = $request->user();
        $data = $request->validated();
        $testplan = $request->testplan;

        try {
            DB::transaction(function () use ($testplan, $data, $user) {
                $oldData = $testplan->toArray();

                $testplan->update([
                    'title' => $data['title'],
                    'description' => HtmlSanitizer::clean($data['description']),
                    'category_id' => $data['categoryType'],
                    'case_information' => $data['caseInformation'],
                    'main_status' => $data['mainstatus'],
                    'version_type' => $data['versionType'],
                    'version_number' => $data['versionNumber'] ?? $testplan->version_number,
                    'type_of_status' => $data['typeOfStatus'] ?? $testplan->type_of_status,
                    'priority' => $data['priority'] ?? $testplan->priority,
                    'item_data_file' => $data['existingFiles'] ?? $testplan->item_data_file,
                ]);

                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'Updated Testplan Status: '.strtoupper($testplan->main_status),
                    'data' => ['before' => $oldData, 'after' => $testplan->toArray()],
                ]);
            });

            return response()->json(['result' => 'success', 'message' => 'Updated successfully']);
        } catch (\Throwable $e) {
            Log::error('Testplan update failed', ['exception' => $e, 'testplan_id' => $testplan->id]);

            return response()->json(['result' => 'error', 'message' => self::GENERIC_ERROR], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $testplan = Testplan::find($id);
        if (! $testplan) {
            return response()->json(['result' => 'error', 'message' => 'Testplan not found'], 404);
        }

        if (! $user->can('manage', $testplan)) {
            return response()->json(['result' => 'error', 'message' => 'Permission denied'], 403);
        }

        try {
            DB::transaction(function () use ($testplan, $user, $id) {
                $snapshot = $testplan->toArray();
                $title = $testplan->title;

                // Assignments are removed by the assignments.testplan_id ON DELETE CASCADE foreign key.
                $testplan->delete();

                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => "Deleted Entire Testplan: {$title}",
                    'data' => $snapshot,
                    'description' => "User ({$user->permission_level}) deleted Testplan #{$id} and all associated assignments.",
                ]);
            });

            return response()->json(['result' => 'success', 'message' => 'Testplan and all assignments deleted successfully']);
        } catch (\Throwable $e) {
            Log::error('Testplan destroy failed', ['exception' => $e, 'testplan_id' => $id]);

            return response()->json(['result' => 'error', 'message' => self::GENERIC_ERROR], 500);
        }
    }
}
