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
        Schema::create('invitation_recipients', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('greeting', 500)->nullable();
            $table->unsignedTinyInteger('max_guests')->default(1);
            $table->char('token_hash', 64)->unique();
            $table->text('token_ciphertext');
            $table->unsignedInteger('token_version')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('challenge_started_at')->nullable();
            $table->timestamp('challenge_completed_at')->nullable();
            $table->timestamp('revealed_at')->nullable();
            $table->timestamp('attempt_window_started_at')->nullable();
            $table->timestamp('challenge_locked_until')->nullable();
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->unsignedTinyInteger('hints_used')->default(0);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['invitation_id', 'revoked_at']);
            $table->index(['invitation_id', 'opened_at']);
            $table->index(['invitation_id', 'challenge_completed_at']);
            $table->index(['invitation_id', 'revealed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_recipients');
    }
};
