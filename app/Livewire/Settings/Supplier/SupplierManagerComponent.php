<?php

namespace App\Livewire\Settings\Supplier;

use App\Classes\Settings;
use App\Models\Supplier;
use App\Traits\LivewireAlert;
use Livewire\Component;

class SupplierManagerComponent extends Component
{

    use LivewireAlert;


    protected $listeners = [
        'editSupplier' => 'edit',
    ];

    public String $modalTitle = "New";

    public String $saveButton = "Save";

    public String $searchTerms = "";

    public int $filter;

    public string $type;

    public array $filters = [];

    public $cities;

    public String $modalName = "Supplier";

    public string $modelId = "";

    public string $name ="";
    public string $phonenumber ="";
    public string $email ="";
    public string $address  = "";

    public  $supplier = NULL;
    public function mount()
    {
        $this->type = "";
    }

    public function booted()
    {
        $this->cacheModel = "suppliers";
    }

    public function render()
    {
        return view('livewire.settings.supplier.supplier-manager-component');
    }


    public function edit($id)
    {
        $this->modalTitle = "Update";

        $this->modelId = $id;

        $supplier = Supplier::find($id);

        $this->name = $supplier->name ;
        $this->phonenumber = $supplier->phonenumber;
        $this->email = $supplier->email ?? "" ;
        $this->address  = $customer->address ?? "";

        $this->saveButton = "Update";

        $this->dispatch("openModal", []);
    }

    public function new()
    {
        $this->modalTitle = "New";

        $this->modelId =  "";

        $this->name = "";
        $this->phonenumber ="";
        $this->email = "";
        $this->address = "";

        $this->saveButton = "Save";

        $this->dispatch("openModal", []);
    }


    public function update(Supplier $supplier)
    {
        $supplier->name = $this->name ;
        $supplier->phonenumber = $this->phonenumber;
        $supplier->email = $this->email ?? "" ;
        $supplier->address  = $this->address ?? "";

        $supplier->save();

        $this->alert(
            "success",
            "Customer",
            [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' => "Supplier has been updated successfully"
            ]
        );

        $this->dispatch("closeModal", []);

        $this->dispatch('refreshData');

        return true;
    }


    public function saveSupplier()
    {
        $this->validate([
            'name' => 'required',
            'phonenumber' => 'required|digits_between:11,11'
        ]);


        $supplier = new Supplier();

        $supplier->name = $this->name ;
        $supplier->phonenumber = $this->phonenumber;
        $supplier->email = $this->email ?? "" ;
        $supplier->address  = $this->address ?? "";

        $supplier->save();

        $this->alert(
            "success",
            "Supplier",
            [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' => "Supplier has been created successfully"
            ]
        );

        $this->dispatch("closeModal", []);

        $this->dispatch('refreshData');

        return true;
    }


    public function toggle($id)
    {
        $model = Supplier::find($id);
        $model->status = !$model->status;
        $model->save();
    }

    public function get()
    {
        $suppliers =  Supplier::where('id','>',1)->where('retail_customer', $this->filter);

        if($this->searchTerms !== "" && \Str::length($this->searchTerms) > 3)
        {
            $searchTerms = explode(" ", $this->searchTerms);

            foreach ($searchTerms as $searchTerm)
            {
                $suppliers->where(function($query) use($searchTerm){

                    $query->orwhere('name', 'LIKE', "%$searchTerm%");
                    $query->orwhere('phaone', 'LIKE', "%$searchTerm%");
                });

            }
        }
        return $suppliers->orderBy('firstname', 'ASC')->paginate(Settings::$pagination);
    }
}
