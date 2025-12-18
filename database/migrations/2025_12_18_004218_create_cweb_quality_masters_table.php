<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_cweb_quality_masters_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cweb_quality_masters', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cweb_quality_masters');
    }
};
