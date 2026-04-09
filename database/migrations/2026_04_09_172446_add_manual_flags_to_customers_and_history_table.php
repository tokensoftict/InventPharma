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
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_manual_member_group')->default(false)->after('member_group_id');
            $table->boolean('is_manual_retail_member_group')->default(false)->after('retail_member_group_id');
        });

        Schema::table('member_group_histories', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('recalculation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['is_manual_member_group', 'is_manual_retail_member_group']);
        });

        Schema::table('member_group_histories', function (Blueprint $table) {
            $table->dropColumn('is_manual');
        });
    }
};
