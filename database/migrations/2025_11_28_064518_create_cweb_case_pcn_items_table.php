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
    Schema::create('cweb_case_pcn_items', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->foreignId('case_id')->constrained('cweb_cases')->onDelete('cascade');
        $table->string('category'); // spec / man / machine / material / method / measurement / environment / other
        $table->string('title')->nullable(); // ラベル変更など
        $table->integer('months_before')->default(0);
        $table->text('note')->nullable();
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('cweb_case_pcn_items');
}

};
