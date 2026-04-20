<?php

namespace App\Console\Commands;

use App\Jobs\PushDataServerNoQueue;
use App\Models\User;
use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use Illuminate\Console\Command;

class SyncAllStaffToKafka extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-all-staff';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all users (staff) to Kafka';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting staff synchronization...');

        User::query()->chunk(100, function ($users) {
            foreach ($users as $user) {
                dispatch(new PushDataServerNoQueue([
                    'KAFKA_ACTION' => KafkaAction::SYNC_STAFF,
                    'KAFKA_TOPICS' => KafkaTopics::GENERAL,
                    'action' => 'new',
                    'table' => 'staffs',
                    'endpoint' => 'staffs',
                    'data' => $user->getBulkPushData()
                ]));
            }
            $this->info('Synced ' . $users->count() . ' users...');
        });

        $this->info('Staff synchronization completed.');
    }
}
