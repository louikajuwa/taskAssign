<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip',
    ];

    public function upload(Request $request)
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'max:20480', 'mimes:'.implode(',', self::ALLOWED_EXTENSIONS)],
        ]);

        $files = $request->file('files');

        // Resolve and verify every extension (by content sniffing) before storing anything.
        $prepared = [];
        foreach ($files as $file) {
            $ext = strtolower((string) $file->guessExtension());
            if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'File type not allowed.',
                    'errors' => ['files' => ['File type not allowed.']],
                ], 422);
            }
            $prepared[] = [$file, $ext];
        }

        $urls = [];
        foreach ($prepared as [$file, $ext]) {
            $path = $file->storeAs('uploads', Str::random(40).'.'.$ext, 'public');
            $urls[] = Storage::url($path);
        }

        return response()->json([
            'result' => 'success',
            'urls' => $urls,
        ]);
    }
}
