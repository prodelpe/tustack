<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * jobs:fetch and companies:enrich have always written "partial" when some
     * of their work failed, and the column never accepted it. With MySQL in
     * strict mode that turned a night with one failed query into a crash at
     * the very end, with nothing recorded and no report sent.
     */
    public function up(): void
    {
        Schema::table('command_logs', function (Blueprint $table) {
            $table->enum('status', ['running', 'success', 'partial', 'failed'])->default('running')->change();
        });
    }

    public function down(): void
    {
        Schema::table('command_logs', function (Blueprint $table) {
            $table->enum('status', ['running', 'success', 'failed'])->default('running')->change();
        });
    }
};
