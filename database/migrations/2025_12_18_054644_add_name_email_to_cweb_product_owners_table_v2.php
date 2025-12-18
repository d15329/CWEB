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
public function up(): void
{
    Schema::table('cweb_product_owners', function (Blueprint $table) {
        $table->string('name', 100)->nullable();
        $table->string('email', 255)->nullable();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cweb_product_owners', function (Blueprint $table) {
            //
        });
    }
};
