<?php

namespace App\Livewire\ProductModule\BinCard;

use App\Classes\ExportDataTableComponent;
use App\Classes\Settings;
use App\Models\Stockbincard;
use App\Traits\SimpleDatatableComponentTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Classes\Column;

class ProductBincard extends ExportDataTableComponent
{

    use SimpleDatatableComponentTrait;

    protected $model = Stockbincard::class;

    public array $filters = [];

    public static $bincardType = [
        'APP//RECEIVED'=>"RECEIVED",
        "APP//TRANSFER"=>"TRANSFER",
        "APP//SOLD"=>"SOLD",
        "APP//RETURN"=>"RETURN",
        "APP//DEPARTMENT"=>"DEPARTMENT",
        "APP//BRANCH"=>"BRANCH",
        "APP//BATCH_UPDATE"=>"BATCH UPDATE"
    ];


    public function builder(): Builder
    {
       $date = $this->filters['between.stockbincards.bin_card_date'];
       $dept = $this->filters['department'];
       $stock = $this->filters['stock_id'];

        return Stockbincard::query()->where('stock_id', $stock)->whereBetween('bin_card_date',$date)->where(function($q) use(&$dept, &$stock){
            $q->orwhere("from_department",$dept)
                ->orwhere('to_department',$dept)
                ->orwhere('bin_card_type',"APP//BATCH_UPDATE");
        })->orderBy('id','DESC');

    }

    public static function  mountColumn() : array
    {
        return [
            Column::make("Type", "bin_card_type")
                ->sortable()
                ->format(fn($value, $row, Column $column) => self::$bincardType[$value])
                ->searchable(),
            Column::make("In qty", "in_qty")
                ->sortable()->searchable(),
            Column::make("Out qty", "out_qty")
                ->sortable()->searchable(),
            Column::make("From", "from_department")
                ->format(fn($value, $row, Column $column) => $value == "" ? "" : Settings::$department[$value])
                ->sortable()->searchable(),
            Column::make("To", "to_department")
                ->format(fn($value, $row, Column $column) => $value == "" ? "" : Settings::$department[$value])
                ->sortable()->searchable(),
            Column::make("Sold qty", "sold_qty")
                ->sortable()->searchable(),
            Column::make("Return qty", "return_qty")
                ->sortable()->searchable(),
            Column::make("Balance", "department_balance")
                ->sortable()->searchable(),
            Column::make("Grand Balance", "balance")
                ->sortable()->searchable(),
            Column::make("Date", "bin_card_date")
                ->sortable()->searchable(),
            Column::make("By", "user.name")
                ->sortable()->searchable(),
            Column::make("Comment", "comment")
                ->format(fn($value, $row, Column $column) => $value)->html()
                ->sortable(),
        ];
    }



}
