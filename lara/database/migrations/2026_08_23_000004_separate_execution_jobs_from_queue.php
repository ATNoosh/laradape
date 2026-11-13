<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'pipeline_name')) {
            Schema::rename('jobs', 'execution_jobs');
        }

        if (! Schema::hasTable('execution_jobs')) {
            Schema::create('execution_jobs', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('queue')->index();
                $table->string('pipeline_name');
                $table->text('payload')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
