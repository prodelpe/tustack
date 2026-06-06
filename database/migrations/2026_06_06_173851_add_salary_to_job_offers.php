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
        Schema::table('job_offers', function (Blueprint $table) {
            $table->unsignedInteger('salary_min')->nullable()->after('published_at');
            $table->unsignedInteger('salary_max')->nullable()->after('salary_min');
            $table->boolean('salary_is_predicted')->nullable()->after('salary_max');
        });
    }

    public function down(): void
    {
        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropColumn(['salary_min', 'salary_max', 'salary_is_predicted']);
        });
    }
};
