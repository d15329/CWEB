<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_cweb_product_owners_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cweb_product_owners', function (Blueprint $table) {
            $table->id();
            $table->string('product_group', 100);
            $table->string('product_code', 50);
            $table->string('employee_number', 10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_group', 'product_code', 'employee_number'], 'cweb_prod_owner_unique');
            $table->index(['product_group', 'product_code'], 'cweb_prod_owner_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cweb_product_owners');
    }
};
