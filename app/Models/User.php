<?php

namespace App\Models;

use App\Models\Concerns\HasUnixTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, HasUnixTimestamps;

    protected $fillable = [
        'name',
        'email',
        'password',
        'permission_level',
        'allow_creation',
        'email_notifications_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'allow_creation' => 'boolean',
            'email_notifications_enabled' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->permission_level, [
            'Team Leader (TL)',
            'Project Manager (PM) & Integrator',
        ]);
    }

    public function canCreate(): bool
    {
        return $this->isAdmin() || $this->allow_creation;
    }

    public function testplans()
    {
        return $this->hasMany(Testplan::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'author_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }
}
