<?php

namespace App\Livewire\ProductModule\NearOS;

use App\Models\Nearoutofstock;
use App\Models\Stock;
use App\Models\Stockgroup;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\LivewireAlert;


class ViewNearOsGroupedStock extends Component
{
    use LivewireAlert;

    public $stockgroup;

    public $nearos;

    public function boot()
    {

    }

    public function mount(Stockgroup $stockgroup)
    {
        $this->stockgroup = $stockgroup;
        $this->nearos = Nearoutofstock::query()
            ->select(
                [
                    'nearoutofstocks.*',
                    'stocks.id as stock_id',
                    'stocks.name as stock_name',
                    'stocks.box as box',
                    'stocks.carton as carton',
                    'categories.name as category_name',
                    'suppliers.name as supplier_name',
                    'stockgroups.name as group_name',
                    DB::raw('(CASE
                        WHEN nearoutofstocks.stockgroup_id IS NOT NULL THEN stockgroups.name
                        ELSE stocks.name
                    END) AS name')
                ]
            )
            ->leftJoin('stocks', function ($stocks) {
                $stocks->on('nearoutofstocks.stock_id', '=', 'stocks.id');
            })
            ->leftJoin('categories', 'stocks.category_id', '=', 'categories.id')
            ->leftJoin('stockgroups', function ($stockgroups) {
                $stockgroups->on('nearoutofstocks.stockgroup_id', '=', 'stockgroups.id');
            })
            ->leftJoin('suppliers', function ($suppliers) {
                $suppliers->on('nearoutofstocks.supplier_id', '=', 'suppliers.id');
            })->whereIn('nearoutofstocks.stock_id', $this->stockgroup->stocks->pluck('id'))->get();


    }

    public function render()
    {
        return view('livewire.product-module.near-os.view-near-os-grouped-stock');
    }
}