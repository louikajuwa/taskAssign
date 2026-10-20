<?php

namespace App\Models;

use App\Models\Concerns\HasUnixTimestamps;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasUnixTimestamps;

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'to_email',
        'subject',
        'type',
        'status',
        'error',
        'dedup_key',
        'attempt',
    ];
}
