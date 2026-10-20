<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /** Admin-only category management page (route is behind AdminMiddleware). */
    public function manage()
    {
        return view('categories.index');
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = Category::query();
        if (! empty($search)) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $categories = $query->withCount('testers')->orderBy('name')->get();

        $result = $categories->map(function ($cat) {
            $arr = $cat->toArray();
            $arr['category_name'] = $cat->name;
            $arr['tester_count'] = $cat->testers_count;

            return $arr;
        });

        return response()->json(['result' => 'success', 'data' => $result]);
    }

    public function lightweight(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = Category::query();
        if (! empty($search)) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $result = $query->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'category_name' => $c->name]);

        return response()->json(['result' => 'success', 'data' => $result]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return response()->json(['result' => 'error', 'message' => 'Permission denied'], 403);
        }

        $name = trim($request->input('categoryName', ''));
        if (empty($name)) {
            return response()->json(['result' => 'error', 'message' => 'Category name is required.'], 400);
        }

        $existing = Category::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            return response()->json(['result' => 'error', 'message' => 'Category already exists.'], 409);
        }

        $cat = Category::create([
            'name' => $name,
            'created_by' => $user->id,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => "Added New Category: {$name}",
            'data' => $cat->toArray(),
            'description' => "User ({$user->permission_level}) created a new test plan category.",
        ]);

        $data = $cat->toArray();
        $data['category_name'] = $cat->name;

        return response()->json(['result' => 'success', 'message' => 'Category added successfully', 'data' => $data]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return response()->json(['result' => 'error', 'message' => 'Permission denied'], 403);
        }

        $cat = Category::find($id);
        if (! $cat) {
            return response()->json(['result' => 'error', 'message' => 'Category not found.'], 404);
        }

        $oldData = $cat->toArray();
        $name = $cat->name;

        DB::transaction(function () use ($cat, $user, $id, $name, $oldData) {
            $cat->delete();

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => "Deleted Category (ID: {$id}): {$name}",
                'data' => $oldData,
                'description' => "User ({$user->permission_level}) deleted the category '{$name}' (ID: {$id}).",
            ]);
        });

        return response()->json(['result' => 'success', 'message' => 'Category removed successfully']);
    }
}
