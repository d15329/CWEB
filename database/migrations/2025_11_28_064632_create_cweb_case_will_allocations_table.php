<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
{
    Schema::create('cweb_case_will_allocations', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->foreignId('case_id')->constrained('cweb_cases')->onDelete('cascade');
        $table->string('employee_number');
        $table->string('employee_name')->nullable();
        $table->integer('percentage'); // 0〜100
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('cweb_case_will_allocations');
}
};
