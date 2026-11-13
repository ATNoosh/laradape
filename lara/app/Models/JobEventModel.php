<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class JobEventModel extends Model
{
    protected $table = 'execution_job_events';

    protected $fillable = [
        'job_id', 'event_type', 'stage', 'status', 'message', 'payload',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
