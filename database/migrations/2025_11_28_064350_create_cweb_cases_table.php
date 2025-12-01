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
    Schema::create('cweb_cases', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('management_no')->unique(); // SP-250001 等
        $table->string('status')->default('active'); // active / abolished

        $table->string('customer_name');
        $table->string('sales_contact_employee_number')->nullable();
        $table->string('cost_responsible_code')->nullable();

        $table->boolean('category_standard')->default(false);
        $table->boolean('category_pcn')->default(false);
        $table->boolean('category_other')->default(false);

        $table->string('product_group')->nullable(); // HogoMax-内製品 等
        $table->string('product_code')->nullable();  // 103 等

        $table->text('pcn_note')->nullable();
        $table->text('other_request_note')->nullable();

        $table->integer('will_registration_cost')->nullable();
        $table->text('will_registration_comment')->nullable();
        $table->integer('will_monthly_cost')->nullable();
        $table->text('will_monthly_comment')->nullable();

        $table->text('related_qweb')->nullable();
        $table->string('folder_path')->nullable();

        $table->foreignId('created_by_user_id')->constrained('users');
        $table->foreignId('abolished_by_user_id')->nullable()->constrained('users');

        $table->text('abolished_comment')->nullable();
        $table->timestamp('abolished_at')->nullable();

        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('cweb_cases');
}

};
