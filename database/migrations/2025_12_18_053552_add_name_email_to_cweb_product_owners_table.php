<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cweb_product_owners', function (Blueprint $table) {
            if (!Schema::hasColumn('cweb_product_owners', 'name')) {
                $table->string('name', 100)->nullable();
            }
            if (!Schema::hasColumn('cweb_product_owners', 'email')) {
                $table->string('email', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cweb_product_owners', function (Blueprint $table) {
            if (Schema::hasColumn('cweb_product_owners', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('cweb_product_owners', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
