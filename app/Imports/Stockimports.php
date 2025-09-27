<?php


namespace App\Imports;

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
        foreach ($rows as $row){

            if(!isset($row['id'])) continue;

            $stock = Stock::find($row['id']);

            if(!$stock) continue;

            if(isset($row['name'])){

                $stock->name =  $row['name'];  //update product name
            }

            if(isset($row['category']) && !empty($row['category'])  && $row['category'] != "N/A"){

                if($row['category'] == "NILL"){

                    $stock->category_id = NULL;

                }else {

                    $category = Category::where('name', $row['category'])->get()->first();

                    if ($category) {
                        $category = $category->id;
                    } else {
                        $pc = Category::create(['name' => $row['category'], 'status' => 1]);
                        $category = $pc->id;
                    }

                $stock->category_id = $category;
                }

            }


            if(isset($row['manufacturer']) && !empty($row['manufacturer']) &&  $row['manufacturer'] != "N/A"){

                if($row['manufacturer'] == "NILL") {
                    $stock->manufacturer_id = NULL;
                }else{

                    $manufacturer = Manufacturer::where('name', $row['manufacturer'])->get()->first();
                    if ($manufacturer) {
                        $manufacturer = $manufacturer->id;
                    } else {
                        $mn = Manufacturer::create(['name' => $row['manufacturer'], 'status' => 1]);
                        $manufacturer = $mn->id;
                    }

                    $stock->manufacturer_id = $manufacturer;
                }

            }


            if(isset($row['classification']) && $row['classification'] != "N/A" && !empty($row['classification'])) {

                if($row['classification'] == "NILL"){
                    $stock->classification_id = NULL;
                }else {
                    $classification = Classification::where('name', $row['classification'])->get()->first();
                    if ($classification) {
                        $classification = $classification->id;
                    } else {
                        $cl = Classification::create(['name' => $row['classification'], 'status' => 1]);
                        $classification = $cl->id;
                    }
                    $stock->classification_id = $classification;
                }
            }


            if(isset($row['group']) && $row['group'] != "N/A" && !empty($row['group'])) {

                if($row['group'] == "NILL"){
                    $stock->stockgroup_id =  NULL;
                }else {
                    $group = StockGroup::where('name', $row['group'])->get()->first();

                    if ($group) {
                        $group = $group->id;
                    } else {
                        $gp = StockGroup::create(['name' => $row['group'], 'status' => 1]);
                        $group = $gp->id;
                    }

                    $stock->stockgroup_id = $group;
                }
            }


            if(isset($row['retail_price']) && !empty($row['retail_price'])){

                $stock->retail_price = $row['retail_price'];
            }

            if(isset($row['whole_sales_price']) ){

                $stock->whole_price = $row['whole_sales_price'];
            }

            if(isset($row['bulk_sales_price']) ){

                $stock->bulk_price = $row['bulk_sales_price'];
            }

            if(isset($row['status']) ){

                $stock->status = (int)$row['status'];
            }

            if(isset($row['reorder']) ){

                $stock->reorder = (int)$row['reorder'];
            }
            if(!isset($row['id'])) continue;

            $stock = Stock::find($row['id']);

            if(!$stock) continue;

            if(isset($row['name'])){

                $stock->name =  $row['name'];  //update product name
            }

            if(isset($row['category']) && !empty($row['category'])  && $row['category'] != "N/A"){

                if($row['category'] == "NILL"){

                    $stock->category_id = NULL;

                }else {

                    $category = Category::where('name', $row['category'])->get()->first();

                    if ($category) {
                        $category = $category->id;
                    } else {
                        $pc = Category::create(['name' => $row['category'], 'status' => 1]);
                        $category = $pc->id;
                    }

                    $stock->category_id = $category;
                }

            }


            if(isset($row['manufacturer']) && !empty($row['manufacturer']) &&  $row['manufacturer'] != "N/A"){

                if($row['manufacturer'] == "NILL") {
                    $stock->manufacturer_id = NULL;
                }else{

                    $manufacturer = Manufacturer::where('name', $row['manufacturer'])->get()->first();
                    if ($manufacturer) {
                        $manufacturer = $manufacturer->id;
                    } else {
                        $mn = Manufacturer::create(['name' => $row['manufacturer'], 'status' => 1]);
                        $manufacturer = $mn->id;
                    }

                    $stock->manufacturer_id = $manufacturer;
                }

            }


            if(isset($row['classification']) && $row['classification'] != "N/A" && !empty($row['classification'])) {

                if($row['classification'] == "NILL"){
                    $stock->classification_id = NULL;
                }else {
                    $classification = Classification::where('name', $row['classification'])->get()->first();
                    if ($classification) {
                        $classification = $classification->id;
                    } else {
                        $cl = Classification::create(['name' => $row['classification'], 'status' => 1]);
                        $classification = $cl->id;
                    }
                    $stock->classification_id = $classification;
                }
            }


            if(isset($row['group']) && $row['group'] != "N/A" && !empty($row['group'])) {

                if($row['group'] == "NILL"){
                    $stock->stockgroup_id =  NULL;
                }else {
                    $group = StockGroup::where('name', $row['group'])->get()->first();

                    if ($group) {
                        $group = $group->id;
                    } else {
                        $gp = StockGroup::create(['name' => $row['group'], 'status' => 1]);
                        $group = $gp->id;
                    }

                    $stock->stockgroup_id = $group;
                }
            }


            if(isset($row['retail_price']) && !empty($row['retail_price'])){

                $stock->retail_price = $row['retail_price'];
            }

            if(isset($row['whole_sales_price']) ){

                $stock->whole_price = $row['whole_sales_price'];
            }

            if(isset($row['bulk_sales_price']) ){

                $stock->bulk_price = $row['bulk_sales_price'];
            }

            if(isset($row['status']) ){

                $stock->status = (int)$row['status'];
            }

            if(isset($row['reorder']) ){

                $stock->reorder = (int)$row['reorder'];
            }

            if(isset($row['box'])){

                $stock->box = $row['box'];
            }


            $stock->update();
        }
    }

}
