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
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        $systemOptions = [
            //'radio',
            //'checkbox',
            //'text',
            //'textarea',
            //'file',
            //'date',
            //'time',
            //'datetime',
            'select',
        ];

        foreach ($systemOptions as $option) {
            DB::table('options')->insert(['type' => $option]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
