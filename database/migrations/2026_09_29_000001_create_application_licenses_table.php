<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the application_licenses table.
     *
     * NOTE: This table is a DATABASE CACHE ONLY.
     * The authoritative activation data is the cryptographically signed
     * activation.dat file verified by ApplicationActivationService.
     * Changes to this table's expires_at are detected and treated as tampering.
     */
    public function up(): void
    {
        Schema::create('application_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('installation_id', 64)->index();
            $table->string('activation_id', 64)->nullable()->index();
            $table->string('product', 128)->default('pharmacy_inventory');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'invalid', 'tampered'])->default('invalid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_licenses');
    }
};
