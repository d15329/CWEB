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
    Schema::table('cweb_cases', function (Blueprint $table) {
        $table->string('manage_no', 20)->nullable()->index();
    });
}

public function down()
{
    Schema::table('cweb_cases', function (Blueprint $table) {
        $table->dropColumn('manage_no');
    });
}
};
