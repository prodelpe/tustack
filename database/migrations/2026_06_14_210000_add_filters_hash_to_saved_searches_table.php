<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicates keeping the earliest per (user_id, filters)
        DB::statement('
            DELETE s1 FROM saved_searches s1
            INNER JOIN saved_searches s2
            WHERE s1.user_id = s2.user_id
              AND s1.filters = s2.filters
              AND s1.id > s2.id
        ');

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->string('filters_hash', 32)->nullable()->after('filters');
        });

        // Backfill hash for existing rows
        DB::table('saved_searches')->get()->each(function ($row) {
            $filters = json_decode($row->filters, true);
            $normalized = [
                'technologies' => $filters['technologies'] ?? [],
                'provinces'    => $filters['provinces'] ?? [],
                'query'        => $filters['query'] ?? '',
            ];
            DB::table('saved_searches')
                ->where('id', $row->id)
                ->update(['filters_hash' => md5(json_encode($normalized))]);
        });

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->unique(['user_id', 'filters_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'filters_hash']);
            $table->dropColumn('filters_hash');
        });
    }
};
