<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A single row (id = 1), edited in the admin area. Never put real values into the repository.
        Schema::create('legal_settings', function (Blueprint $table) {
            $table->id();
            $table->string('operator_name', 120)->nullable();
            $table->string('operator_address', 500)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('imprint_extra')->nullable();
            $table->string('hosting_provider', 500)->nullable();
            $table->text('processors')->nullable();
            $table->string('supervisory_authority', 500)->nullable();
            $table->text('privacy_extra')->nullable();
            $table->timestampsTz();
        });

        DB::table('legal_settings')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_settings');
    }
};
