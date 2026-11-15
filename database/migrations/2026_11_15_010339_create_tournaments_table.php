<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('game');
            $table->string('format')->nullable();
            $table->string('capacity_value')->nullable();
            $table->string('capacity_label')->nullable();
            $table->json('phases')->nullable();
            $table->string('rules_url')->nullable();
            $table->string('cta_label')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
