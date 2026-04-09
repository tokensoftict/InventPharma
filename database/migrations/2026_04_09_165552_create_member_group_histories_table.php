<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('member_group_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('old_member_group_id')->nullable();
            $table->unsignedBigInteger('new_member_group_id')->nullable();
            $table->string('type'); // 'retail' or 'other'
            $table->decimal('total_spent', 15, 2);
            $table->date('recalculation_date');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('old_member_group_id')->references('id')->on('member_groups')->onDelete('set null');
            $table->foreign('new_member_group_id')->references('id')->on('member_groups')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_group_histories');
    }
};
