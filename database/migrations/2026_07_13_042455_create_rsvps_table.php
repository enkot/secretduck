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
        Schema::create('rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->unique()->constrained('invitation_recipients')->cascadeOnDelete();
            $table->string('respondent_name');
            $table->string('response')->index();
            $table->unsignedTinyInteger('guest_count');
            $table->text('dietary_notes')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};
