<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 12)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('admin_token_hash', 64)->unique();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('location', 200)->nullable();
            $table->string('organizer_name', 80);
            $table->string('organizer_email');
            $table->boolean('allow_maybe')->default(true);
            $table->boolean('notify_on_vote')->default(false);
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('final_option_id')->nullable();
            $table->timestampTz('finalized_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->timestampsTz();

            $table->index(['poll_id', 'starts_at']);
        });

        Schema::table('polls', function (Blueprint $table) {
            $table->foreign('final_option_id')->references('id')->on('poll_options')->nullOnDelete();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);
            // Lower-cased; one vote per address and poll.
            $table->string('email');
            $table->char('edit_token_hash', 64)->unique();
            $table->timestampsTz();

            $table->unique(['poll_id', 'email']);
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->string('answer', 10);
            $table->timestampsTz();

            $table->unique(['participant_id', 'poll_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
        Schema::dropIfExists('participants');
        Schema::table('polls', fn (Blueprint $table) => $table->dropForeign(['final_option_id']));
        Schema::dropIfExists('poll_options');
        Schema::dropIfExists('polls');
    }
};
