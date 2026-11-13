<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('execution_jobs', function (Blueprint $table): void {
            $table->string('current_stage')->nullable();
            $table->string('current_hop')->nullable();
            $table->unsignedSmallInteger('progress_percent')->default(0);
            $table->unsignedInteger('files_total')->default(0);
            $table->unsignedInteger('files_changed')->default(0);
            $table->unsignedInteger('files_review')->default(0);
            $table->text('last_message')->nullable();
        });

        Schema::create('execution_job_events', function (Blueprint $table): void {
            $table->id();
            $table->string('job_id')->index();
            $table->string('event_type')->index();
            $table->string('stage')->nullable();
            $table->string('status')->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['job_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_job_events');
        Schema::table('execution_jobs', function (Blueprint $table): void {
            $table->dropColumn([
                'current_stage', 'current_hop', 'progress_percent',
                'files_total', 'files_changed', 'files_review', 'last_message',
            ]);
        });
    }
};
