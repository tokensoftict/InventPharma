<?php


namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Stockexport implements FromArray, WithHeadings
{

    protected $stocks;

    public function __construct(array $stocks)
    {
        $this->stocks = $stocks;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        return $this->stocks;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return  [
            'ID',
            'name',
            'Category',
            'Manufacturer',
            'Classification',
            'Group',
            'Retail Price',
            'Whole Sales Price',
            'Bulk Sales Price',
            'Status',
            'Quantity',
            'Last purchase Date',
            'Box'
        ];
    }
}
