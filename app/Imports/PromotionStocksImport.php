<?php

namespace App\Imports;

use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PromotionStocksImport implements ToCollection, WithHeadingRow
{

    private Promotion $promotion;

    public function __construct(Promotion $promotion)
    {
        $this->promotion = $promotion;
    }

    public function collection(Collection $collection)
    {
        $stocks = Stock::where('status', 1)->pluck('id')->toArray();

        foreach ($collection as $row) {
            if(!in_array($row['stock_id'], $stocks)) continue;

            $data = [
                'promotion_id' => $this->promotion->id,
                'user_id' => auth()->id(),
                'status_id' => status('Pending'),
                'from_date' => $this->promotion->from_date->format('Y-m-d'),
                'end_date' => $this->promotion->end_date->format('Y-m-d'),
                'created' => $this->promotion->created->format('Y-m-d'),
                'stock_id' => $row['stock_id']
            ];

            if(!empty($row['wholesales_price'])){
                $data['whole_price'] = $row['wholesales_price'];
            }else{
                $data['whole_price'] =0;
            }

            if(!empty($row['retail_price'])){
                $data['retail_price'] = $row['retail_price'];
            }else{
                $data['retail_price'] = 0;
            }

            if(!empty($row['bulk_price'])){
                $data['bulk_price'] = $row['bulk_price'];
            }else{
                $data['bulk_price'] = 0;
            }

            PromotionItem::create($data);
        }
    }
}
