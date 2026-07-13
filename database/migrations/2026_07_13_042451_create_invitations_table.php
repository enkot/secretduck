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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft')->index();
            $table->string('title')->nullable();
            $table->string('host_names')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->string('timezone')->nullable();
            $table->string('venue_name')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('dress_code')->nullable();
            $table->timestamp('rsvp_deadline_at')->nullable();
            $table->string('map_url', 2048)->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('theme')->default('elegant');
            $table->char('accent_color', 7)->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('reveal_heading')->nullable();
            $table->string('teaser_text', 500)->nullable();
            $table->string('success_message', 500)->nullable();
            $table->unsignedTinyInteger('default_max_guests')->default(1);
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
