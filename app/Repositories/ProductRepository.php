<?php

namespace App\Repositories;

use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class ProductRepository
{

    public function __construct()
    {

    }

    public static array $productFields = [
        'name' => NULL,
        'description'=> NULL,
        'code'=> NULL,
        'category_id'=> NULL,
        'manufacturer_id'=> NULL,
        'classification_id'=> NULL,
        'stockgroup_id'=> NULL,
        'brand_id'=> NULL,
        'whole_price'=> NULL,
        'bulk_price'=> NULL,
        'retail_price'=> NULL,
        'barcode'=> NULL,
        'location'=> NULL,
        'expiry'=> '1',
        'reorder' => 1,
        'piece'=> 1,
        'box'=> 1,
        'carton'=> 1,
        'image_path' =>  NULL,
        'sachet'=> '0',
        'status'=> '1',
        'minimum_quantity' => NULL
    ];


    public function create($data) : Stock{

        return Stock::create($data);
    }


    public function getStock($id) : Stock {
        return Stock::find($id);
    }


    public function update($id, $data) : Stock{

        $stock  = $this->getStock($id);

        $stock->update($data);

        return $stock;
    }


    public function destroy($id) : void
    {
        $this->getStock($id)->delete();
    }


    public function findProductByBarcode($barcode)
    {
        $selling_price = match (request()->column){
            'wholesales', 'bulksales', 'quantity', '', NULL => 'whole_price',
            'retail', 'retail_store'  => 'retail_price',
        };

        $cost_price = match (request()->column){
            'wholesales', 'bulksales', 'quantity', '', NULL => 'cost_price',
            'retail', 'retail_store'  => 'retail_cost_price',
        };


        $stock = DB::table('stocks')->select(
            'stocks.id',
            "stocks.".request()->column.' as quantity',
            "stocks.".$cost_price." as cost_price",
            "stocks.".$selling_price." as selling_price",
            'stocks.name',
            'stocks.box',
            'stocks.location',
            'stocks.name as text',
            'stocks.carton',
            'promotion_items.promotion_id',
            'promotion_items.from_date',
            'promotion_items.end_date',
            'promotion_items.'.$selling_price." as promo_selling_price"
        )
            ->leftJoin('promotion_items', function($join) {
                $join->on('stocks.id', '=', 'promotion_items.stock_id')
                    ->on('promotion_items.status_id', '=', DB::raw(status('Approved')));
            })
            ->join('stockbarcodes', function($join) {
                $join->on('stocks.id', '=', 'stockbarcodes.stock_id');
            })
            ->where('stockbarcodes.barcode', $barcode)
            ->first();

        if ($stock) {
            // Fetch product_custom_prices for this stock
            $customPrices = DB::table('product_custom_prices')
                ->where('stock_id', $stock->id)
                ->get();

            // Attach to the stock object
            $stock->custom_prices = $customPrices;

            return response()->json($stock);
        }

        return response()->json([]);
    }

    public function findProduct(mixed $name)
    {
        if(empty($name) || \Str::length($name) < 3) return collect([])->toJson();

        $name = explode(" ",$name);

        $selling_price = match (request()->column){
            'wholesales', 'bulksales', 'quantity', '', NULL => 'whole_price',
            'retail', 'retail_store' => 'retail_price',
        };

        $cost_price = match (request()->column){
            'wholesales', 'bulksales', 'quantity', '', NULL => 'cost_price',
            'retail','retail_store' => 'retail_cost_price',
        };
//->where(request()->column ,'>',0)

        $stocks = DB::table('stocks')
            ->select(
                'stocks.retail_store as retail_store',
                'stocks.id',
                "stocks.".$cost_price." as cost_price",
                "stocks.".$selling_price." as selling_price",
                'stocks.name',
                'stocks.box',
                'stocks.location',
                'stocks.name as text',
                'stocks.carton',
                'promotion_items.promotion_id',
                'promotion_items.from_date',
                'promotion_items.end_date',
                'promotion_items.'.$selling_price." as promo_selling_price",
                DB::raw("SUM(stockbatches.".request()->column.") as quantity")
            )
            ->where( "stocks.$selling_price", ">", 0)
           // ->where("stockbatches.".request()->column, ">", 0)
            ->leftJoin('promotion_items', function($join) use ($selling_price) {
                $join->on('stocks.id', '=', 'promotion_items.stock_id')
                    ->where('promotion_items.status_id', '=', DB::raw(status('Approved')))
                    ->where('promotion_items.'.$selling_price, '>', 0);
            })
            ->where(function($query) use (&$name) {
                foreach ($name as $char) {
                    $query->where('stocks.name', 'LIKE', "%$char%");
                }
            })
            ->leftJoin('stockbatches', "stocks.id", '=',"stockbatches.stock_id" )
            ->groupBy("stocks.id")
            ->get();

// Get all custom prices for the stock IDs
        $stockIds = $stocks->pluck('id');

        $customPrices = DB::table('product_custom_prices')
            ->whereIn('stock_id', $stockIds)
            ->get()
            ->groupBy('stock_id');

// Attach custom prices to each stock
        $stocks->transform(function ($stock) use ($customPrices) {
            $stock->custom_prices = $customPrices->get($stock->id, collect())->values();
            return $stock;
        });

        return $stocks->toJson();

    }

    public function findPurchaseProduct(mixed $name)
    {
        if(empty($name) || \Str::length($name) < 3) return collect([])->toJson();

        $name = explode(" ",$name);

        $selling_price = match (request()->column){
            'wholesales', 'bulk-sales', 'quantity', '', NULL => 'whole_price',
            'retail', 'retail_store'  => 'retail_price',
        };

        $cost_price = match (request()->column){
            'wholesales', 'bulk-sales', 'quantity', '', NULL => 'cost_price',
            'retail', 'retail_store'  => 'retail_cost_price',
        };

        return DB::table('stocks')
            ->select('id', $cost_price, $selling_price, 'name', 'box', 'location', 'name as text',
                DB::raw('ROUND((((retail/box) + wholesales + quantity + bulksales)),0) as allqty')
            )->where(function($query) use(&$name){
                foreach ($name as $char) {
                    $query->where('stocks.name', 'LIKE', "%$char%");
                }
            })->get()->toJson();

    }


}
