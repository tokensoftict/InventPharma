<?php

namespace App\Console\Commands;

use App\Imports\StockClassificationImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportStockClassification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:stock-classification {filename=classification_import.xlsx}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import stock classifications from an Excel file in the storage folder';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filename = $this->argument('filename');
        $path = storage_path($filename);

        if (!file_exists($path)) {
            $this->error("File not found at: {$path}");
            return Command::FAILURE;
        }

        $this->info("Starting import from {$filename}...");

        try {
            Excel::import(new StockClassificationImport, $path);
            $this->info("Import completed successfully!");
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
