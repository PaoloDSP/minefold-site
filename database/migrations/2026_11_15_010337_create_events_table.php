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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->json('badges')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('hours_label')->nullable();
            $table->string('venue')->nullable();
            $table->string('address')->nullable();
            $table->string('registration_label')->nullable();
            $table->text('intro')->nullable();
            $table->string('helloasso_form_type')->default('Event');
            $table->string('helloasso_form_slug')->nullable();
            $table->string('checkout_url')->nullable();
            $table->string('challenge_title')->nullable();
            $table->text('challenge_text')->nullable();
            $table->string('challenge_games')->nullable();
            $table->json('challenge_modes')->nullable();
            $table->json('program')->nullable();
            $table->text('program_note')->nullable();
            $table->text('byoc_intro')->nullable();
            $table->json('byoc_items')->nullable();
            $table->text('rewards_intro')->nullable();
            $table->json('rewards')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('map_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('helloasso_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
