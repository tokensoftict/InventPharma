<?php

namespace App\Livewire\ProductModule;

use App\Models\OptionField;
use App\Models\OptionFieldValue;
use App\Models\Stock;
use App\Models\StockOption;
use App\Models\StockOptionValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductOptionComponent extends Component
{
    public Stock $product;

    public $options;

    public string $option = "";

    public string $modalTitle = "";

    public $stockOptions;

    public $optionValues = [];



    //modal livewire emodel
    public $selectedOptionFieldId;
    public $stock_option_id, $option_field_value_id, $retail_price_prefix, $wholesales_price_prefix = "";
    public  $retail_price, $wholesales_price = "0";
    public $retail_status, $wholesales_status = "0";
    public $stockOptionAction = "create";
    public $updatingStockOptionValue = "";

    public function mount()
    {
        $this->options = OptionField::with('option', 'option_field_values')
                        ->where('status', 1)
                        ->get();

    }


    public function submitOption()
    {
        $this->validate([
            'option' => [
                'required',
                Rule::unique('stock_options', 'option_field_id')
                    ->where('stock_id', $this->product->id),
            ],
        ]);

        StockOption::create([
            'stock_id' => $this->product->id,
            'option_field_id' => $this->option,
        ]);

    }


    public function submitOptionValue()
    {
        $this->validate([
            'option_field_value_id' => 'required',
            'retail_price' => 'required',
            'retail_price_prefix' => 'required',
            'wholesales_price_prefix' => 'required',
            'wholesales_price' => 'required',
            'retail_status' => 'required',
            'wholesales_status' => 'required',
        ]);

        $stockOption = StockOption::with('option_field', 'option_field.option')->find($this->stock_option_id);

        $insertData = [
            'stock_option_id' => $this->stock_option_id,
            'option_field_value_id' => $this->option_field_value_id,
            'stock_id' => $this->product->id,
            'option_id' => $stockOption->option_field->option->id,
            'status' => true,
            "retail_price" => $this->retail_price,
            'retail_price_prefix' => $this->retail_price_prefix,
            'wholesales_price_prefix' => $this->wholesales_price_prefix,
            'wholesales_price' => $this->wholesales_price,
            'required' => true,
            'quantity' => 0,
            'retail_status' => $this->retail_status,
            'wholesales_status' => $this->wholesales_status,

        ];

        StockOptionValue::create($insertData);
        $this->dispatch('successNotification', ["message" => "Option has been created successfully"]);
        $this->dispatch("closeModal", []);
    }


    public function deleteStockOption(string $stockOptionId)
    {
        DB::transaction(function () use ($stockOptionId) {
            StockOptionValue::query()->where('stock_option_id', $stockOptionId)->delete();
            StockOption::find($stockOptionId)->delete();
        });

        $this->dispatch('successNotification', ["message" => "Stock Option has been deleted successfully"]);
        $this->dispatch("closeModal", []);
    }


    public function newStockOptionValues(string $optionFieldID, string $stockOptionId)
    {
        $this->option_field_value_id ="";
        $this->retail_price_prefix = "+";
        $this->retail_price = "0";

        $this->wholesales_price_prefix = "+";
        $this->wholesales_price = "0";

        $this->stock_option_id = $stockOptionId;
        $this->selectedOptionFieldId = $optionFieldID;


        $selectedOptionFieldValueId = StockOptionValue::query()->where('stock_option_id', $this->stock_option_id)->pluck("option_field_value_id")->toArray();
        $this->optionValues = OptionFieldValue::query()
            ->whereNotIn('id', $selectedOptionFieldValueId)
            ->where('option_field_id', $this->selectedOptionFieldId)->get();

        $this->stockOptionAction = "create";
        $this->dispatch("openModal", []);
    }


    public function editStockOptionValues(string $stockOptionValueID)
    {
        $stockOptionValue = StockOptionValue::with(['option_field_value', 'option_field_value.option_field'])->find($stockOptionValueID);
        $this->updatingStockOptionValue = $stockOptionValueID;


        $selectedOptionFieldValueId = StockOptionValue::query()->where('stock_option_id', $stockOptionValue->stock_option_id)->pluck("option_field_value_id")->toArray();
        $selectedOptionFieldValueId = array_diff($selectedOptionFieldValueId, [$stockOptionValue->option_field_value_id]);
        $this->optionValues = OptionFieldValue::query()
            ->whereNotIn('id', array_values($selectedOptionFieldValueId))
            ->where('option_field_id', $stockOptionValue->option_field_value->option_field->id)->get();

        $this->option_field_value_id = $stockOptionValue->option_field_value_id;

        $this->retail_price = $stockOptionValue->retail_price;
        $this->retail_price_prefix = $stockOptionValue->retail_price_prefix;
        $this->retail_status = $stockOptionValue->retail_status;

        $this->wholesales_price = $stockOptionValue->wholesales_price;
        $this->wholesales_price_prefix = $stockOptionValue->wholesales_price_prefix;
        $this->wholesales_status = $stockOptionValue->wholesales_status;

        $this->stockOptionAction = "update";
        $this->dispatch("openModal", []);
    }


    public function updateOptionValue(string $stockOptionValueID)
    {
        $this->validate([
            'option_field_value_id' => 'required',
            'retail_price' => 'required',
            'retail_price_prefix' => 'required',
            'wholesales_price_prefix' => 'required',
            'wholesales_price' => 'required',
            'retail_status' => 'required',
            'wholesales_status' => 'required',
        ]);

        $stockOptionValue = StockOptionValue::with(['option_field_value', 'option_field_value.option_field'])->find($stockOptionValueID);

        $updatedData= [
            'option_field_value_id' => $this->option_field_value_id,
            'stock_id' => $this->product->id,
            'status' => true,
            "retail_price" => $this->retail_price,
            'retail_price_prefix' => $this->retail_price_prefix,
            'wholesales_price_prefix' => $this->wholesales_price_prefix,
            'wholesales_price' => $this->wholesales_price,
            'required' => true,
            'quantity' => 0,
            'retail_status' => $this->retail_status,
            'wholesales_status' => $this->wholesales_status,
        ];

        $stockOptionValue->update($updatedData);
        $this->dispatch('successNotification', ["message" => "Stock Option has been updated successfully"]);
        $this->dispatch("closeModal", []);
    }


    public function deleteStockOptionValues(string $stockOptionValueID)
    {
        $stockOptionValue = StockOptionValue::with(['option_field_value', 'option_field_value.option_field'])->find($stockOptionValueID);
        DB::transaction(function () use ($stockOptionValue) {
            $stockOptionValue->delete();
        });
        $this->dispatch('successNotification', ["message" => "Stock Option has been deleted successfully"]);
    }

    public function render()
    {
        $this->stockOptions = StockOption::query()->with(['option_field', 'stock_option_values', 'stock_option_values.option_field_value'])->where('stock_id', $this->product->id)->orderBy('id', 'desc')->get();
        return view('livewire.product-module.product-option-component');
    }
}
