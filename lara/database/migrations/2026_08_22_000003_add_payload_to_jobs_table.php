<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('execution_jobs', 'payload')) {
            Schema::table('execution_jobs', function (Blueprint $table): void {
                $table->text('payload')->nullable()->after('pipeline_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('execution_jobs', 'payload')) {
            Schema::table('execution_jobs', function (Blueprint $table): void {
                $table->dropColumn('payload');
            });
        }
    }
};
