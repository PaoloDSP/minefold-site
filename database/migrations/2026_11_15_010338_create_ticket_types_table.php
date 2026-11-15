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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('tag')->nullable();
            $table->string('name');
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('price_label')->nullable();
            $table->json('perks')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('capacity_label')->nullable();
            $table->unsignedBigInteger('helloasso_tier_id')->nullable();
            $table->unsignedInteger('entries_taken')->nullable();
            $table->unsignedInteger('max_entries')->nullable();
            $table->string('checkout_url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
