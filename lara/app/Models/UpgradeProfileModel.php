<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UpgradeProfileModel extends Model
{
    protected $table = 'upgrade_profiles';

    protected $fillable = [
        'name',
        'description',
        'configuration',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}
