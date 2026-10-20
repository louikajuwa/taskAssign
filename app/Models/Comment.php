<?php

namespace App\Models;

use App\Models\Concerns\HasUnixTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory, HasUnixTimestamps;

    protected $fillable = [
        'testplan_id',
        'assignment_role_id',
        'target_user_id',
        'author_id',
        'comment',
        'comment_file',
        'thread_id',
    ];

    protected function casts(): array
    {
        return [
            'comment_file' => 'array',
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function testplan()
    {
        return $this->belongsTo(Testplan::class);
    }
}
