<?php

namespace App\Livewire\ProductModule;

use App\Classes\Settings;
use App\Models\Category;
use App\Models\DependentProduct;
use App\Models\ProductCustomPrice;
use App\Models\Stock;
use App\Models\Stockbarcode;
use App\Repositories\ProductRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Traits\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductComponent extends Component
{

    use LivewireAlert, WithFileUploads;

    public $categories;

    public $brands;

    public $stockgroups;

    public $classifications;

    public $manufacturers;

    public Stock $product;

    public array $product_data = [];

    public array $barcodes = [];

    public array $productPriceBasedOnQuantity = [];
    public string $productPriceBasedDepartment = '';

    private ProductRepository $productRepository;


    public array $dependentProduct = [
        'stock_id',
        'parent',
        'child'
    ];

    public function boot(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function booted()
    {
        $this->categories = categories(true);
        $this->brands = brands(true);
        $this->manufacturers = manufacturers(true);
        $this->stockgroups = stockgroups(true);
        $this->classifications = classifications(true);
    }

    public function mount()
    {

        if(isset($this->product->id))
        {
            $this->product_data = Arr::only( $this->product->toArray(), array_keys(ProductRepository::$productFields));
        }
        else {
            $this->product_data = ProductRepository::$productFields;
        }

        $this->barcodes = $this->product->stockbarcodes->map(function($barcode){
            return str_replace("\r", "",$barcode->barcode);
        })->toArray();

    }

    public function render()
    {
        return view('livewire.product-module.product-component');
    }

    public function saveStock()
    {
        $data = [
            "product_data.name"=>"bail|required|max:255",
            "product_data.piece"=>"bail|required",
            "product_data.carton"=>"bail|required",
            "product_data.box"=>"bail|required",
            "product_data.location"=>"required",
            "product_data.store_type"=>"required"
        ];

        if(config('app.sync_with_online') === 1 && $this->product_data['image_path'] === "logo/" . app(Settings::class)->store()->logo)
        {
            $data['product_data.image_path'] = 'mimes:jpeg,jpg,png,bmp|required|max:10000';
        }

        if(userCanView('product.changeSellingPrice'))
        {
            if(department_by_quantity_column('wholesales', false)->status) {
                $data["product_data.whole_price"] = "required";
            }
            if(department_by_quantity_column('bulksales', false)->status) {
                $data["product_data.bulk_price"] = "required";
            }
            if(department_by_quantity_column('retail', false)->status) {
                $data["product_data.retail_price"] = "required";
            }
        }
        else {

            Arr::forget($this->product_data, ['whole_price','bulk_price','retail_price']);

        }

        if($this->product_data['image_path'] !== NULL && !is_string($this->product_data['image_path']))
        {
            $data['product_data.image_path'] = 'mimes:jpeg,jpg,png,bmp|required|max:10000';
        }

        $this->validate($data);

        if($this->product_data['image_path'] !== NULL && !is_string($this->product_data['image_path'])) {
            $this->product_data['image_path'] = "products/".$this->product_data['image_path']->store('images', 'product_images');
            $this->product_data['image_download_status'] = "COMPLETE";
            $this->product_data['image_uploaded'] = "0";
        }

        if(isset($this->product->id))
        {
            $message = "updated";

            $this->product_data['user_id'] = auth()->id();

            $this->product =  $this->productRepository->update($this->product->id, $this->product_data);
        }
        else {
            $message = "created";

            $this->product_data['user_id'] = auth()->id();

            $this->product =  $this->productRepository->create($this->product_data);

            $this->product_data = ProductRepository::$productFields;

        }

        $this->alert(
            "success",
            "Product",
            [
                'position' => 'center',
                'timer' => 12000,
                'toast' => false,
                'text' =>  "Product has been ".$message." successfully!.",
            ]
        );

        //return redirect()->route('product.edit', $this->product->id);
        return redirect()->route('product.available', $this->product->id);

    }

    public function validateBarcode($code)
    {
        $code = trim(preg_replace('/\s\s+/', ' ', $code));
        $code = str_replace("\r", "",$code);
        $barcode = Stockbarcode::where('barcode', $code)->first();
        if($barcode){
            if($barcode->stock_id == $this->product->id){
                $this->alert(
                    "error",
                    "Product",
                    [
                        'position' => 'center',
                        'timer' => 3000,
                        'toast' => false,
                        'text' =>  "Barcode already exist for this product.",
                    ]
                );
            }else if($barcode->stock_id != $this->product->id){
                $this->alert(
                    "error",
                    "Product",
                    [
                        'position' => 'center',
                        'timer' => 3000,
                        'toast' => false,
                        'text' =>  "Barcode is already added to ".$barcode->stock->name." please remove it and try again",
                    ]
                );
            }
            return ['status' => true];
        }else if(in_array($code, $this->barcodes)){
            $this->alert(
                "error",
                "Product",
                [
                    'position' => 'center',
                    'timer' => 1000,
                    'toast' => false,
                    'text' =>  "Barcode is already exist in the list",
                ]
            );
            return ['status' => false];
        }else{
            $this->barcodes[] = $code;
            return ['status' => false];
        }

    }


    public function openBundlePriceModal(string $department)
    {
        $this->productPriceBasedDepartment = $department;

        $this->productPriceBasedOnQuantity = $this->product->stockquantityprices()->where('department', $this->productPriceBasedDepartment)->get()->map(function($price){
            return [
                'id' => $price->id,
                'price' => $price->price,
                'department' => $price->department,
                'min_qty' => $price->min_qty,
                'max_qty' => $price->max_qty,
            ];
        })->toArray();

        $this->dispatch('openBundleSettingsModal', []);
    }

    public function saveProductPrice(array $prices)
    {
        $price = array_map(function($price) {
            $price['stock_id'] = $this->product->id;
            $price['user_id'] = auth()->id();
            $price['department'] = $this->productPriceBasedDepartment;
            return new ProductCustomPrice($price);
        }, $prices);

        $status = DB::transaction(function() use($price) {
            $this->product->stockquantityprices()->where('department', $this->productPriceBasedDepartment)->delete();
            if(count($price) > 0) {
                $this->product->stockquantityprices()->saveMany($price);
            }
            $this->alert(
                "success",
                "Product",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' =>  "Quantity Based Price has been updated successfully!",
                ]
            );
            return true;
        });


        $this->productPriceBasedOnQuantity = $this->product->stockquantityprices()->where('department', $this->productPriceBasedDepartment)->get()->map(function($price){
            return [
                'id' => $price->id,
                'price' => $price->price,
                'department' => $price->department,
                'min_qty' => $price->min_qty,
                'max_qty' => $price->max_qty,
            ];
        })->toArray();

        return $status;
    }

    public function saveBarcode()
    {
        DB::transaction(function(){
            $this->product->stockbarcodes()->delete();
            $barcodes = collect($this->barcodes)->map(function($barcode){
                return new Stockbarcode([
                    'stock_id' => $this->product->id,
                    'barcode'  => trim(preg_replace('/\s\s+/', ' ', $barcode)) ,
                    'user_id'  => auth()->id()
                ]);
            });

            $this->product->stockbarcodes()->saveMany($barcodes);

            $this->alert(
                "success",
                "Product",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' =>  "Barcode has been updated successfully!",
                ]
            );
        });

    }

    public function deleteBarcode($code)
    {
        $this->barcodes =  $this->product->stockbarcodes->map(function($barcode){
            return $barcode->barcode;
        })->filter(function($barcode) use ($code){
            return $barcode !== $code;
        })->toArray();
    }


    public function addDependentproduct()
    {
        $this->validate([
            'dependentProduct.stock_id' => 'required',
            'dependentProduct.parent' => 'required',
            'dependentProduct.child' => 'required',
        ],
            [
                'dependentProduct.stock_id.required' => 'Please select a stock',
                'dependentProduct.parent.required' => 'Parent field is required.',
                'dependentProduct.child.required' => 'Child field is required.',
            ]
        );

        $data = $this->dependentProduct;
        $data['parent_stock_id'] = $this->product->id;

        DependentProduct::create($data);

        $this->dependentProduct = [
            'stock_id' => "",
            'parent' => "",
            'child' => "",
        ];

        $this->dispatch('successNotification', ["message" => "Dependent Product has been added successfully"]);
    }


    public function removeDependentproduct($dependentProductId)
    {
        DB::transaction(function() use ($dependentProductId){
            DependentProduct::find($dependentProductId)->delete();
        });

        $this->dispatch('successNotification', ["message" => "Dependent Product has been deleted successfully"]);
    }


}
