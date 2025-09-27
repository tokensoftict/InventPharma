<?php

namespace App\Livewire\Customer;

use App\Classes\Settings;
use App\Models\City;
use App\Models\Customer;

use App\Traits\LivewireAlert;
use Livewire\Component;

class CustomerManagerComponent extends Component
{

    use LivewireAlert;

    protected $listeners = [
        'editCustomer' => 'edit',
    ];

    public String $modalTitle = "New";

    public String $saveButton = "Save";

    public String $searchTerms = "";

    public int $filter;

    public string $type;

    public array $filters = [];

    public $cities;

    public String $modalName = "Customer";

    public string $modelId = "";


    public String $firstname = "";
    public String $lastname = "";
    public String $address = "";
    public String $phone_number = "";
    public String $email = "";
    public String $city_id = "";

    public  $customer = NULL;


    public function mount()
    {
        $this->type = "";

        $this->cities = City::all();
    }

    public function render()
    {
        return view('livewire.customer.customer-manager-component');
    }


    public function edit($id)
    {
        $this->modalTitle = "Update";

        $this->modelId = $id;

        $customer = Customer::find($id);

        $this->firstname = $customer->firstname ;
        $this->lastname = $customer->lastname;
        $this->email = $customer->email ?? "" ;
        $this->phone_number = $customer->phone_number;
        $this->address  = $customer->address ?? "";
        $this->city_id = $customer->city_id ?? "";


        $this->saveButton = "Update";

        $this->dispatch("openModal", []);
    }


    public function new()
    {

        $this->modalTitle = "New";

        $this->firstname = "";
        $this->lastname ="";
        $this->email = "";
        $this->phone_number = "";
        $this->address  ="";
        $this->city_id ="";

        $this->saveButton = "Save";

        $this->dispatch("openModal", []);
    }


    public function update(Customer $customer)
    {
        $customer_ = Customer::where('phone_number',$this->phone_number)->where('status',1)->get()->first();

        if($customer_ && $customer_->id  != $customer->id)
        {
            $this->alert(
                "error",
                "Customer",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' =>"Customer Phone Number already exists with name ".$customer_->firstname." ".$customer_->lastname
                ]
            );
            return false;
        }

        $customer->firstname = $this->firstname;
        $customer->lastname = $this->lastname;
        $customer->email = $this->email ?? "";
        $customer->phone_number = $this->phone_number;
        $customer->address = $this->address;
        $customer->retail_customer = (auth()->user()->department_id === 4 ? 1 : 0);
        $customer->city_id = $this->city_id == "" ? NULL : $this->city_id;

        $customer->save();

        $this->dispatch("closeModal", []);

        return true;
    }


    public function saveCustomers()
    {
        $this->validate([
            'firstname' =>'required',
            'lastname' => 'required',
            'phone_number' => 'required|digits_between:11,11|unique:customers,phone_number,'.($this->modelId ?? "")
        ]);

        $this->customer = Customer::where('phone_number',  $this->phone_number)->where('status',1)->get()->first();

        if(!$this->customer)
        {
            if($this->modelId)
            {
                $this->customer = Customer::find($this->modelId);
            }
        }

        if(!$this->customer) {
            $status = $this->save();
            $message = "Customer has been created successfully";
        }else{
            $status = $this->update($this->customer);
            $message = "Customer has been updated successfully";
        }

        $this->dispatch('refreshData');

        if($status  === true) {
            $this->alert(
                "success",
                "Customer",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' => $message
                ]
            );
        }

    }

    public function save()
    {
        $this->validate([
            'firstname' =>'required',
            'lastname' => 'required',
            'phone_number' => 'required|digits_between:11,11|unique:customers,phone_number',
        ]);

        $this->modelId = null;

        $customer = new Customer();

        $customer->firstname = $this->firstname;
        $customer->lastname = $this->lastname;
        $customer->email = $this->email;
        $customer->phone_number = $this->phone_number;
        $customer->address = $this->address;
        $customer->retail_customer = (auth()->user()->department_id === 4 ? 1 : 0);
        $customer->city_id = $this->city_id == "" ? NULL : $this->city_id;

        $customer->save();

        $this->dispatch("closeModal", []);

        return true;
    }


    public function toggle($id)
    {
        $model = Customer::find($id);
        $model->status = !$model->status;
        $model->save();
    }


    public function get()
    {
        $customers =  Customer::where('id','>',1)->where('retail_customer', $this->filter);

        if($this->searchTerms !== "" && \Str::length($this->searchTerms) > 3)
        {
            $searchTerms = explode(" ", $this->searchTerms);

            foreach ($searchTerms as $searchTerm)
            {
                $customers->where(function($query) use($searchTerm){

                    $query->orwhere('firstname', 'LIKE', "%$searchTerm%");
                    $query->orwhere('lastname', 'LIKE', "%$searchTerm%");
                    $query->orwhere('email', 'LIKE', "%$searchTerm%");
                    $query->orwhere('phone_number', 'LIKE', "%$searchTerm%");
                });

            }
        }
        return $customers->orderBy('firstname', 'ASC')->paginate(Settings::$pagination);

    }

}
