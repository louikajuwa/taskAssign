<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Testplan;
use App\Rules\StorageFileUrl;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    private const GENERIC_ERROR = 'Something went wrong. Please try again.';

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->all();
        $sessionId = $user->id;

        $assignId = (int) ($data['assignId'] ?? 0);
        $userId = (int) ($data['targetUserId'] ?? 0);
        $idAssign = (int) ($data['idAssign'] ?? 0);

        if (! $assignId || ! $userId || ! $idAssign) {
            return response()->json(['result' => 'error', 'message' => 'Missing required fields'], 400);
        }

        $validator = Validator::make($data, [
            'comment' => ['nullable', 'string', 'max:5000'],
            'comment_file' => ['nullable', 'array', 'max:20'],
            'comment_file.*' => ['string', new StorageFileUrl],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'result' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $comment = trim((string) ($data['comment'] ?? ''));
        $itemUrls = array_values($data['comment_file'] ?? []);

        // UI allows a file-only message (comment text empty, attachments present).
        if ($comment === '' && empty($itemUrls)) {
            return response()->json(['result' => 'error', 'message' => 'Comment is required'], 400);
        }

        $assign = Assignment::where('testplan_id', $assignId)
            ->where('user_id', $userId)
            ->where('role_id', $idAssign)
            ->first();

        if (! $assign) {
            return response()->json(['result' => 'error', 'message' => 'Assignment not found'], 404);
        }

        $testplan = Testplan::find($assignId);
        if (! $testplan) {
            return response()->json(['result' => 'error', 'message' => 'Testplan not found'], 404);
        }

        if (! $user->can('comment', [$testplan, $assign])) {
            return response()->json(['result' => 'error', 'message' => 'Permission denied'], 403);
        }

        try {
            $commentId = DB::transaction(function () use ($assign, $assignId, $idAssign, $userId, $sessionId, $comment, $itemUrls) {
                $locked = Assignment::lockForUpdate()->find($assign->id);

                if (empty($locked->comment_thread_id)) {
                    $locked->comment_thread_id = Str::uuid()->toString();
                    $locked->save();
                }
                $assign->comment_thread_id = $locked->comment_thread_id;

                Comment::create([
                    'testplan_id' => $assignId,
                    'assignment_role_id' => $idAssign,
                    'target_user_id' => $userId,
                    'author_id' => $sessionId,
                    'comment' => $comment,
                    'comment_file' => $itemUrls,
                    'thread_id' => $locked->comment_thread_id,
                ]);

                return $locked->comment_thread_id;
            });
        } catch (\Throwable $e) {
            Log::error('Comment store failed', ['exception' => $e]);

            return response()->json(['result' => 'error', 'message' => self::GENERIC_ERROR], 500);
        }

        try {
            app(AlertService::class)->notifyCommentAdded($testplan, $assign, $comment, $itemUrls, $user);
        } catch (\Throwable $e) {
            Log::error('Comment alert failed', ['exception' => $e, 'testplan_id' => $assignId]);
        }

        return response()->json([
            'result' => 'success',
            'message' => 'Comment added successfully',
            'files' => $itemUrls,
            'commentId' => $commentId,
        ]);
    }

    public function index(Request $request)
    {
        $commentId = trim((string) $request->input('commentId', ''));
        if ($commentId === '') {
            return response()->json(['result' => 'error', 'message' => 'commentId is required'], 400);
        }

        // Resolve the thread to its testplan and require participation (prevents reading other threads by id).
        $threadAssign = Assignment::where('comment_thread_id', $commentId)->first();
        $testplan = $threadAssign ? Testplan::find($threadAssign->testplan_id) : null;
        if (! $testplan) {
            return response()->json(['result' => 'error', 'message' => 'Thread not found'], 404);
        }

        if (! $request->user()->can('participate', $testplan)) {
            return response()->json(['result' => 'error', 'message' => 'Permission denied'], 403);
        }

        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 10)));

        $query = Comment::where('thread_id', $commentId);
        $totalRecords = $query->count();

        $comments = $query->with('author')
            ->orderBy('id', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $results = $comments->map(function ($c) {
            $row = $c->toArray();
            $row['userEmail'] = $c->author->email ?? null;
            $row['whoCreated'] = $c->target_user_id;
            $row['whichIdPlan'] = $c->testplan_id;
            $row['idAssign'] = $c->assignment_role_id;
            $row['idUser'] = $c->author_id;
            $row['commentFile'] = $c->comment_file;
            $row['commentId'] = $c->thread_id;
            $row['created'] = $c->created_at->timestamp;

            return $row;
        });

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
}
