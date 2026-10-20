<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Category;
use App\Models\CategoryTester;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Testplan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /** Roles a user account may hold. */
    private const ROLES = [
        'Developer',
        'Team Leader (TL)',
        'Project Manager (PM) & Integrator',
        'QA',
        'Designer',
    ];

    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
            'roles' => self::ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'permission_level' => ['required', Rule::in(self::ROLES)],
            'allow_creation' => 'sometimes|boolean',
        ], [
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'permission_level.in' => 'Please choose a valid role.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'permission_level' => $data['permission_level'],
            'allow_creation' => $request->boolean('allow_creation'),
        ]);

        return response()->json([
            'result' => 'success',
            'message' => 'User created successfully.',
            'id' => $user->id,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'permission_level' => ['required', Rule::in(self::ROLES)],
            'allow_creation' => 'sometimes|boolean',
        ], [
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'permission_level.in' => 'Please choose a valid role.',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->permission_level = $data['permission_level'];
        $user->allow_creation = $request->boolean('allow_creation');

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return response()->json([
            'result' => 'success',
            'message' => 'User updated successfully.',
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        // You can never delete the account you're logged in as.
        if ($user->id === $request->user()->id) {
            return response()->json([
                'result' => 'error',
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        // Preserve referential integrity & history: refuse if the user owns real work.
        $hasWork = Testplan::where('created_by', $user->id)->exists()
            || Assignment::where('user_id', $user->id)->orWhere('assigned_by', $user->id)->exists()
            || Comment::where('author_id', $user->id)->orWhere('target_user_id', $user->id)->exists()
            || Category::where('created_by', $user->id)->exists();

        if ($hasWork) {
            return response()->json([
                'result' => 'error',
                'message' => 'This user has created test plans, assignments, comments, or categories and cannot be deleted. Their history must be preserved.',
            ], 409);
        }

        DB::beginTransaction();
        try {
            $email = $user->email;

            // Clean up audit/link rows that would otherwise block deletion.
            ActivityLog::where('user_id', $user->id)->delete();
            CategoryTester::where('user_id', $user->id)->delete();
            Notification::where('user_id', $user->id)->delete();

            $user->delete();

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => "Deleted user account: {$email}",
                'description' => "Admin ({$request->user()->email}) deleted user account {$email}.",
            ]);

            DB::commit();

            return response()->json([
                'result' => 'success',
                'message' => 'User deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Admin user delete failed', ['exception' => $e]);

            return response()->json([
                'result' => 'error',
                'message' => 'Could not delete user. Please try again.',
            ], 500);
        }
    }
}
