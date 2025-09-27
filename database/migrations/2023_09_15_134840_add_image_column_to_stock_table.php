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
        Schema::table('stocks', function (Blueprint $table) {
            $table->string('image_download_status')->after('location')->nullable();
            $table->boolean('image_uploaded')->after('location')->nullable();
            $table->string('image_path')->after('location')->nullable();
        });

        \Illuminate\Support\Facades\DB::table('stocks')->update(['image_download_status' => 'PENDING', 'image_uploaded'=>'PENDING']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['image_download_status', 'image_uploaded', 'image_path']);
        });
    }
};
