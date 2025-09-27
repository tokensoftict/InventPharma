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
        Schema::create('stocks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name')->index()->nullable();
            $table->mediumText("description")->nullable();
            $table->string("code",20)->nullable()->index();
            $table->foreignId("category_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("manufacturer_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("classification_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("stockgroup_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("brand_id")->nullable()->constrained()->nullOnDelete();
            $table->decimal("whole_price")->nullable()->index();
            $table->decimal("bulk_price")->nullable()->index();
            $table->decimal("retail_price")->nullable()->index();
            $table->decimal("cost_price")->nullable()->index();
            $table->decimal("retail_cost_price")->nullable()->index();
            $table->bigInteger("wholesales")->default(0)->index();
            $table->bigInteger("bulksales")->default(0)->index();
            $table->bigInteger("retail")->default(0)->index();
            $table->bigInteger("quantity")->default(0)->index();
            $table->string("barcode",200)->nullable()->index();
            $table->mediumText("location")->nullable();
            $table->boolean("expiry")->default(1);
            $table->integer("piece")->default(0);
            $table->integer("box")->default(0);
            $table->integer("carton")->default(0);
            $table->boolean("sachet")->default(0);
            $table->boolean("status")->default(1);
            $table->bigInteger("batched")->nullable();
            $table->boolean('reorder')->default(1);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('stocks');
        Schema::enableForeignKeyConstraints();
    }
};
