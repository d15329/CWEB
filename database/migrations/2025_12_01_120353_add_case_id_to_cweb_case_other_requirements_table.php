<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cweb_case_other_requirements', function (Blueprint $table) {
            // まだ無ければ追加（NOT NULL にしたいなら後でデータを入れてから制約付ける）
            $table->unsignedBigInteger('case_id')->nullable()->after('id');

            // 外部キー張るなら（PostgreSQLでもOK）
            $table->foreign('case_id')
                  ->references('id')
                  ->on('cweb_cases')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cweb_case_other_requirements', function (Blueprint $table) {
            $table->dropForeign(['case_id']);
            $table->dropColumn('case_id');
        });
    }
};
