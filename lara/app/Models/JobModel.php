<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class JobModel extends Model
{
    protected $table = 'execution_jobs';

    protected $fillable = [
        'id',
        'pipeline_name',
        'status',
        'queue',
        'payload'
        ,'attempts',
        'error',
        'started_at',
        'finished_at'
        ,'current_stage', 'current_hop', 'progress_percent', 'files_total',
        'files_changed', 'files_review', 'last_message'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
