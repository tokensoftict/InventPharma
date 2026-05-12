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
        Schema::table('member_groups', function (Blueprint $table) {
            $table->date('discount_until')->nullable()->after('member_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_groups', function (Blueprint $table) {
            $table->dropColumn('discount_until');
        });
    }
};
