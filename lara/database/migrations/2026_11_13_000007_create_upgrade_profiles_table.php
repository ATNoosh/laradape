<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upgrade_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->json('configuration');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upgrade_profiles');
    }
};
