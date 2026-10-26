<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->unsignedBigInteger('default_destination_id')->nullable();
            $table->string('timezone', 64)->default('Africa/Lagos');

            // schedule
            $table->boolean('schedule_enabled')->default(false);
            $table->string('frequency', 32)->default('daily');
            $table->string('schedule_time', 5)->default('02:00');
            $table->unsignedTinyInteger('day_of_week')->default(0);   // 0 = Sunday
            $table->unsignedTinyInteger('day_of_month')->default(1);
            $table->timestamp('schedule_anchor_at')->nullable();      // slots before this are never "missed"

            $table->string('backup_type', 32)->default('database');

            // security (password stored encrypted with the app key; never plain text)
            $table->boolean('encryption_enabled')->default(false);
            $table->text('encryption_password')->nullable();
            $table->string('encryption_fingerprint', 64)->nullable();

            // notifications
            $table->boolean('notify_success')->default(false);
            $table->boolean('notify_failure')->default(true);
            $table->json('notify_recipients')->nullable();

            $table->timestamps();
        });

        Schema::create('backup_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 32)->index();                       // local | google_drive | ...
            $table->text('config')->nullable();                        // encrypted JSON (tokens, paths)
            $table->boolean('enabled')->default(false);
            $table->string('status', 32)->default('unconfigured');     // unconfigured|ready|unavailable|auth_expired|error
            $table->string('status_message', 500)->nullable();
            $table->unsignedInteger('retention_keep_last')->nullable();
            $table->unsignedInteger('retention_keep_days')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_successful_backup_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamps();
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 32)->default('database')->index();
            $table->unsignedBigInteger('destination_id')->nullable()->index();
            $table->string('trigger', 32)->default('manual');          // manual|scheduled|restore_safety
            $table->string('schedule_key', 64)->nullable();            // idempotency key for scheduled runs
            $table->string('filename')->nullable();
            $table->string('path', 1000)->nullable();
            $table->string('external_id')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('status', 32)->default('pending')->index(); // pending|running|successful|failed|cancelled|deleted
            $table->boolean('encrypted')->default(false);
            $table->string('verification_status', 32)->default('pending'); // pending|passed|failed
            $table->json('verification_details')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->string('stage_path', 1000)->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            // A scheduler that fires twice for the same slot cannot create two backups.
            $table->unique(['destination_id', 'schedule_key'], 'backups_destination_slot_unique');
            $table->index('created_at');
        });

        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('backup_id')->nullable()->index();
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('level', 16)->default('info');              // info|warning|error
            $table->string('category', 16)->default('event')->index(); // event|audit
            $table->string('event', 64)->index();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('backups');
        Schema::dropIfExists('backup_destinations');
        Schema::dropIfExists('backup_settings');
    }
};
