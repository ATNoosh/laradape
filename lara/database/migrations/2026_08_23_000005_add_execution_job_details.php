<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missing = [];
        if (! Schema::hasColumn('execution_jobs', 'attempts')) {
            $missing[] = 'attempts';
        }
        if (! Schema::hasColumn('execution_jobs', 'error')) {
            $missing[] = 'error';
        }
        if (! Schema::hasColumn('execution_jobs', 'started_at')) {
            $missing[] = 'started_at';
        }
        if (! Schema::hasColumn('execution_jobs', 'finished_at')) {
            $missing[] = 'finished_at';
        }

        if ($missing !== []) {
            Schema::table('execution_jobs', function (Blueprint $table) use ($missing): void {
                if (in_array('attempts', $missing, true)) {
                    $table->unsignedInteger('attempts')->default(0);
                }
                if (in_array('error', $missing, true)) {
                    $table->text('error')->nullable();
                }
                if (in_array('started_at', $missing, true)) {
                    $table->timestamp('started_at')->nullable();
                }
                if (in_array('finished_at', $missing, true)) {
                    $table->timestamp('finished_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('execution_jobs', function (Blueprint $table): void {
            $table->dropColumn(['attempts', 'error', 'started_at', 'finished_at']);
        });
    }
};
