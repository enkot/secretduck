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
        Schema::create('rsvp_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rsvp_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('respondent_name');
            $table->string('response');
            $table->unsignedTinyInteger('guest_count');
            $table->text('dietary_notes')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at');

            $table->index(['rsvp_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rsvp_revisions');
    }
};
