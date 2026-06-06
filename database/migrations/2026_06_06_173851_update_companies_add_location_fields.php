<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('province');
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete()->after('location');
            $table->string('city')->nullable()->after('location');
            $table->string('country')->nullable()->after('location');
            $table->decimal('latitude', 10, 7)->nullable()->after('name');
            $table->decimal('longitude', 10, 7)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropColumn(['province_id', 'city', 'country', 'latitude', 'longitude']);
            $table->string('province')->nullable();
        });
    }
};
