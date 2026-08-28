<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Names that are also ordinary words, measured against the catalogue. */
    private const AMBIGUOUS = ['go', 'express', 'swift'];

    public function up(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            $table->boolean('ambiguous')->default(false)->after('aliases');
        });

        DB::table('technologies')->whereIn('slug', self::AMBIGUOUS)->update(['ambiguous' => true]);
    }

    public function down(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            $table->dropColumn('ambiguous');
        });
    }
};
