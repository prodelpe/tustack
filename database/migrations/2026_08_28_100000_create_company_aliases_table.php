<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_aliases', function (Blueprint $table) {
            $table->id();

            // The name that stops being a company of its own, kept as the board
            // wrote it so an undone merge can restore it.
            $table->string('name');
            $table->string('name_normalized')->index();

            // The company that survives. Null only if it is later deleted.
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            // Both names sorted and joined, so a decision is remembered for the
            // pair and not for one of the names: rejecting Airbus against Airbus
            // Defence and Space must not silence Airbus against Airbus Group.
            $table->string('pair_key')->unique();

            $table->string('status')->default('pending')->index();
            $table->string('source')->default('gemini');

            // The offers this merge moved, so it can be undone.
            $table->json('moved_offer_ids')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_aliases');
    }
};
