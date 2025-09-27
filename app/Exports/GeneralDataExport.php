<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GeneralDataExport implements FromCollection, WithHeadings
{
    var array $data;
    var array $headings;
    /**
    * @return \Illuminate\Support\Collection
    */

    public function __construct(array $data, array $headings)
    {
        $this->data = $data;
        $this->headings = $headings;
    }

    public function collection() : Collection
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
