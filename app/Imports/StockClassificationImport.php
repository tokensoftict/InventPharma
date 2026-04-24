<?php

namespace App\Imports;

use App\Models\Classification;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockClassificationImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $stockId = $row['id'];
            $classificationName = $row['classification'];
            $majorClassification = $row['major_classification'];

            if (empty($classificationName)) {
                continue;
            }

            // Find or create the classification
            $classification = Classification::where('name', $classificationName)->first();
            
            if (!$classification) {
                $classification = new Classification();
                $classification->name = $classificationName;
                $classification->status = 1;
                $classification->major_classification = $majorClassification;
                $classification->save();
                
                if (method_exists($classification, 'newonlinePush')) {
                    $classification->newonlinePush();
                }
            } else {
                $classification->major_classification = $majorClassification;
                $classification->save();
                
                if (method_exists($classification, 'updateonlinePush')) {
                    $classification->updateonlinePush();
                }
            }

            // Update the stock
            if ($stockId) {
                $stock = Stock::find($stockId);
                if ($stock) {
                    $stock->classification_id = $classification->id;
                    $stock->save();
                }
            }
        }
    }
}
