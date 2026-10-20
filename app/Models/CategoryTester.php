<?php

namespace App\Models;

use App\Models\Concerns\HasUnixTimestamps;
use Illuminate\Database\Eloquent\Model;

class CategoryTester extends Model
{
    use HasUnixTimestamps;

    protected $fillable = [
        'category_id',
        'user_id',
        'role_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
