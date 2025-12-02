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
    Schema::table('cweb_case_other_requirements', function (Blueprint $table) {
        $table->text('content')->nullable();
    });
}

public function down()
{
    Schema::table('cweb_case_other_requirements', function (Blueprint $table) {
        $table->dropColumn('content');
    });
}

};
