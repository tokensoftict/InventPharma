<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Stock;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportStoreTypeExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-store-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import store type from storage/app/store_import.xlsx';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting store type import...');

        $filePath = storage_path('app/store_import.xlsx');

        if (!file_exists($filePath)) {
            $this->error('File not found: ' . $filePath);
            return;
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $updatedCount = 0;
        $notFoundCount = 0;

        foreach ($rows as $index => $row) {
            // Skip header row
            if ($index === 0)
                continue;

            $id = $row[0];
            $storeType = $row[1];

            if (!$id || !$storeType)
                continue;

            $stock = Stock::find($id);
            if ($stock) {
                $stock->store_type = $storeType;
                if ($stock->isDirty('store_type')) {
                    $stock->saveQuietly();
                    //$stock->updateonlinePush(); // Call the push method just in case the saving event doesn't trigger it cleanly due to trait structure
                    $updatedCount++;
                }
            } else {
                $notFoundCount++;
            }
        }

        $this->info("Import completed! Updated: {$updatedCount}, Not Found: {$notFoundCount}");
    }
}
