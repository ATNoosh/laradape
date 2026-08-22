<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class JobModel extends Model
{
    protected $table = 'jobs';

    protected $fillable = [
        'id',
        'pipeline_name',
        'status',
        'queue',
        'payload'
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
