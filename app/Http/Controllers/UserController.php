<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function search(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json(['result' => 'success', 'data' => []]);
        }

        $escaped = addcslashes($search, '%_\\');

        $result = User::select('email', 'id', 'permission_level', 'name')
            ->where('email', 'like', '%'.$escaped.'%')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'result' => 'success',
            'data' => $result->map(fn ($u) => [
                'email' => $u->email,
                'userId' => $u->id,
                'permissionLevel' => $u->permission_level,
                'name' => $u->name,
            ]),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'result' => 'success',
            'data' => [
                'userId' => $user->id,
                'email' => $user->email,
                'permissionLevel' => $user->permission_level,
                'allowCreation' => $user->allow_creation ? 'yes' : 'no',
                'name' => $user->name,
                'email_notifications_enabled' => $user->email_notifications_enabled,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'email_notifications_enabled' => 'required|boolean',
        ]);

        $request->user()->update($data);

        return response()->json([
            'result' => 'success',
            'message' => 'Settings updated successfully',
        ]);
    }
}
