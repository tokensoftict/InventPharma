<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;

class UploadImageToServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploadproduct:image';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload Product Image to Server (Contabo Bucket)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $stockImage = Stock::where("image_uploaded", 0)
        //     ->whereNotNull('image_path')
        //     ->orderBy('id', 'desc')
        //     ->first();

        $stockImage = Stock::find(6);

        if (!$stockImage) {
            $this->info('No images to upload.');
            return Command::SUCCESS;
        }

        $imagePath = public_path($stockImage->image_path);

        if (!File::exists($imagePath)) {
            $this->error('File does not exist: ' . $imagePath);
            $stockImage->image_uploaded = 2; // Error state
            $stockImage->save();
            return Command::FAILURE;
        }

        try {
            $extension = File::extension($imagePath);
            $fileName = $stockImage->id . '.' . $extension;
            $destinationPath = 'images/' . $fileName;

            // Upload to Contabo bucket, replacing if it exists
            $uploaded = Storage::disk('contabo')->putFileAs(
                'psgdc',
                new \Illuminate\Http\File($imagePath),
                $fileName
            );

            if ($uploaded) {
                $this->info('Uploaded ' . $stockImage->id . ' to ' . $destinationPath);
                $stockImage->image_uploaded = 1;

                // Dispatch Kafka message to sync with mystore
                dispatch(new PushDataServer([
                    'KAFKA_ACTION' => KafkaAction::UPLOAD_IMAGE,
                    'KAFKA_TOPICS' => KafkaTopics::STOCKS,
                    'action' => "update",
                    'data' => [
                        'local_stock_id' => $stockImage->id,
                        'image_path' => $destinationPath
                    ],
                ]));
            }
            else {
                $this->error('Failed to upload ' . $stockImage->id);
                $stockImage->image_uploaded = 2;
            }
        }
        catch (\Exception $e) {
            $this->error('Error uploading ' . $stockImage->id . ': ' . $e->getMessage());
            $stockImage->image_uploaded = 2;
        }

        $stockImage->save();

        return Command::SUCCESS;
    }
}