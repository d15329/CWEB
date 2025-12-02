<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cweb_case_other_requirements', function (Blueprint $table) {
            // 要求内容
            if (!Schema::hasColumn('cweb_case_other_requirements', 'content')) {
                $table->text('content')->nullable();
            }

            // 対応者社員番号
            if (!Schema::hasColumn('cweb_case_other_requirements', 'responsible_employee_number')) {
                $table->string('responsible_employee_number', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cweb_case_other_requirements', function (Blueprint $table) {
            if (Schema::hasColumn('cweb_case_other_requirements', 'content')) {
                $table->dropColumn('content');
            }
            if (Schema::hasColumn('cweb_case_other_requirements', 'responsible_employee_number')) {
                $table->dropColumn('responsible_employee_number');
            }
        });
    }
};

