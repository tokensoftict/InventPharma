<?php


namespace App\Imports;

use App\Livewire\ProductModule\Batch\StockBatch;
use App\Models\Category;
use App\Models\Classification;
use App\Models\Manufacturer;
use App\Models\Stock;
use App\Models\Stockgroup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Stockbatch as batch;


class Stockimports implements ToCollection, WithChunkReading, ShouldQueue,WithHeadingRow
{


    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
       dd($rows);
    }

}
