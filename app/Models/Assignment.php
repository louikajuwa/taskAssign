<?php

namespace App\Models;

use App\Models\Concerns\HasUnixTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory, HasUnixTimestamps;

    protected $fillable = [
        'testplan_id',
        'assigned_by',
        'user_id',
        'role_id',
        'role_name',
        'level',
        'status',
        'what_status',
        'go_live',
        'description',
        'comment_thread_id',
        'file_info',
        'field_name',
    ];

    protected function casts(): array
    {
        return [
            'file_info' => 'array',
            'role_id' => 'integer',
            'level' => 'integer',
        ];
    }

    public const ROLE_MAP = [
        'asignToDeVId' => ['id' => 1, 'role' => 'Developer'],
        'generecTesterId' => ['id' => 2, 'role' => 'Generic Tester'],
        'sandboxTesterId' => ['id' => 3, 'role' => 'Sandbox Tester'],
        'alphaTesterId' => ['id' => 4, 'role' => 'Alpha Tester'],
        'betaTesterId' => ['id' => 5, 'role' => 'Beta Tester'],
        'liveTesterId' => ['id' => 6, 'role' => 'Live Tester'],
    ];

    public static function fieldForRoleId(int $roleId): ?string
    {
        foreach (self::ROLE_MAP as $field => $info) {
            if ($info['id'] === $roleId) {
                return $field;
            }
        }

        return null;
    }

    /** Testplan comma-separated id column for a role id (e.g. 1 => assign_to_dev_ids). */
    public static function dbFieldForRoleId(int $roleId): ?string
    {
        return match ($roleId) {
            1 => 'assign_to_dev_ids',
            2 => 'generic_tester_ids',
            3 => 'sandbox_tester_ids',
            4 => 'alpha_tester_ids',
            5 => 'beta_tester_ids',
            6 => 'live_tester_ids',
            default => null,
        };
    }

    public static function roleNameForId(int $roleId): ?string
    {
        foreach (self::ROLE_MAP as $info) {
            if ($info['id'] === $roleId) {
                return $info['role'];
            }
        }

        return null;
    }

    public function testplan()
    {
        return $this->belongsTo(Testplan::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
