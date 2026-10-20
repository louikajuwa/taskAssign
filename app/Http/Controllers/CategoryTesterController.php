<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryTester;
use App\Models\User;
use Illuminate\Http\Request;

class CategoryTesterController extends Controller
{
    public function index(Request $request, $categoryId)
    {
        // Admins manage bindings; users allowed to create plans need them for the auto-assign lookup.
        if (! $request->user()->canCreate()) {
            return response()->json(['result' => 'error', 'message' => 'Permission denied'], 403);
        }

        $roleNames = [
            1 => 'Developer',
            2 => 'Generic Tester',
            3 => 'Sandbox Tester',
            4 => 'Alpha Tester',
            5 => 'Beta Tester',
            6 => 'Live Tester',
        ];

        $bindings = CategoryTester::with('user:id,email')
            ->where('category_id', $categoryId)
            ->orderBy('role_id')
            ->get();

        $result = $bindings->map(fn ($b) => [
            'id' => $b->id,
            'category_id' => $b->category_id,
            'userId' => $b->user_id,
            'email' => $b->user->email ?? 'Unknown',
            'role_id' => (int) $b->role_id,
            'roleName' => $roleNames[(int) $b->role_id] ?? 'Unknown',
        ]);

        return response()->json(['result' => 'success', 'data' => $result]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return response()->json(['result' => 'error', 'message' => 'Permission denied'], 403);
        }

        $categoryId = (int) $request->input('categoryId', 0);
        $targetUserId = (int) $request->input('targetUserId', 0);
        $roleId = (int) $request->input('roleId', 0);

        if (! $categoryId || ! $targetUserId || ! $roleId) {
            return response()->json(['result' => 'error', 'message' => 'categoryId, targetUserId, and roleId are required'], 400);
        }

        if ($roleId < 1 || $roleId > 6) {
            return response()->json(['result' => 'error', 'message' => 'Invalid roleId. Must be 1-6'], 400);
        }

        if (! Category::whereKey($categoryId)->exists()) {
            return response()->json(['result' => 'error', 'message' => 'Category not found'], 404);
        }

        if (! User::whereKey($targetUserId)->exists()) {
            return response()->json(['result' => 'error', 'message' => 'User not found'], 404);
        }

        $existing = CategoryTester::where('category_id', $categoryId)
            ->where('user_id', $targetUserId)
            ->where('role_id', $roleId)
            ->exists();

        if ($existing) {
            return response()->json(['result' => 'error', 'message' => 'This user is already assigned to this category with this role'], 400);
        }

        $binding = CategoryTester::create([
            'category_id' => $categoryId,
            'user_id' => $targetUserId,
            'role_id' => $roleId,
        ]);

        return response()->json([
            'result' => 'success',
            'message' => 'Tester assigned to category',
            'data' => $binding->toArray(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (! $user->isAdmin()) {
            return response()->json(['result' => 'error', 'message' => 'Permission denied'], 403);
        }

        $binding = CategoryTester::find($id);
        if (! $binding) {
            return response()->json(['result' => 'error', 'message' => 'Binding not found'], 404);
        }

        $binding->delete();

        return response()->json(['result' => 'success', 'message' => 'Tester removed from category']);
    }
}
