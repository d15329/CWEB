<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cweb_case_other_requirements', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('case_id')
                  ->constrained('cweb_cases')
                  ->onDelete('cascade');

            $table->text('content')->nullable();
            $table->string('responsible_employee_number', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cweb_case_other_requirements');
    }
};
