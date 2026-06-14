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
        Schema::table('companies', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('sector')->nullable()->after('description');
            $table->string('employees')->nullable()->after('sector');
            $table->string('website')->nullable()->after('employees');
            $table->boolean('gemini_enriched')->default(false)->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['description', 'sector', 'employees', 'website', 'gemini_enriched']);
        });
    }
};
