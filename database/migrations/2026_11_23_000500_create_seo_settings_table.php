<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A single row (id = 1); empty texts fall back to the defaults in SeoSetting.
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 60)->nullable();
            $table->string('home_title', 70)->nullable();
            $table->string('home_description', 160)->nullable();
            $table->string('default_description', 160)->nullable();
            $table->string('organization_name', 100)->nullable();
            $table->boolean('indexing_enabled')->default(true);
            $table->text('robots_extra')->nullable();
            $table->text('llms_intro')->nullable();
            $table->boolean('indexnow_enabled')->default(false);
            $table->char('indexnow_key', 32);
            $table->timestampTz('indexnow_submitted_at')->nullable();
            $table->unsignedSmallInteger('indexnow_status')->nullable();
            $table->timestampsTz();
        });

        DB::table('seo_settings')->insert([
            'id' => 1,
            'indexnow_key' => bin2hex(random_bytes(16)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};
