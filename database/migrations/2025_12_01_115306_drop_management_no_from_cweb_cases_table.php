<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cweb_cases', function (Blueprint $table) {
            // ★ いらない古いカラムを削除
            $table->dropColumn('management_no');
        });
    }

    public function down(): void
    {
        Schema::table('cweb_cases', function (Blueprint $table) {
            // ★ 戻すとき用（型は実際のものに合わせて）
            $table->string('management_no')->nullable();
        });
    }
};
