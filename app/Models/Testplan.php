<?php

namespace App\Models;

use App\Models\Concerns\HasUnixTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testplan extends Model
{
    use HasFactory, HasUnixTimestamps;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'case_information',
        'version_type',
        'version_number',
        'type_of_status',
        'priority',
        'main_status',
        'item_data_file',
        'assign_to_dev_ids',
        'generic_tester_ids',
        'sandbox_tester_ids',
        'alpha_tester_ids',
        'beta_tester_ids',
        'live_tester_ids',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'item_data_file' => 'array',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'testplan_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
