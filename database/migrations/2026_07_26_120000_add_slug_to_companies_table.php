<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        Company::query()
            ->whereNull('slug')
            ->orderBy('id')
            ->chunkById(500, function ($companies) {
                foreach ($companies as $company) {
                    $company->slug = Company::uniqueSlug($company->name, $company->id);
                    $company->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
