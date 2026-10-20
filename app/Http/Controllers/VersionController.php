<?php

namespace App\Http\Controllers;

use App\Models\Version;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function index(Request $request)
    {
        $type = trim($request->input('type', ''));

        $allowedEnvs = ['live', 'sandbox', 'alpha', 'beta'];
        if (!in_array($type, $allowedEnvs)) {
            return response()->json(['result' => 'error', 'message' => 'Invalid environment selected'], 400);
        }

        $versions = Version::where('server', 'license')
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->pluck('version');

        return response()->json([
            'result' => 'success',
            'data' => $versions,
        ]);
    }
}
