<?php

use App\Models\Company;
use App\Support\CompanyName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('name_normalized')->nullable()->after('name');

            $table->index('name_normalized');
        });

        Company::withoutSyncingToSearch(function () {
            Company::query()->each(function (Company $company) {
                $company->name_normalized = CompanyName::normalize($company->name);
                $company->saveQuietly();
            });
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['name_normalized']);
            $table->dropColumn('name_normalized');
        });
    }
};
