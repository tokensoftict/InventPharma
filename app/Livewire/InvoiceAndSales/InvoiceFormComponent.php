<?php

namespace App\Livewire\InvoiceAndSales;

use App\Jobs\AddLogToCustomerLedger;
use App\Models\City;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\OutOfStockLog;
use App\Repositories\InvoiceRepository;
use App\Traits\SimpleComponentTrait;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\LivewireAlert;
use Masmerise\Toaster\Toastable;

class InvoiceFormComponent extends Component
{
    use LivewireAlert, SimpleComponentTrait;

    public Invoice $invoice;

    public  $departments;

    public string|null $department = NULL;

    public array $invoiceData;

    public string $department_id = "";

    public string $d = "";

    public array $selectedDepartment = [];

    public $cities;

    private InvoiceRepository $invoiceRepository;


    public String $firstname = "";
    public String $lastname = "";
    public String $address = "";
    public String $phone_number = "";
    public String $email = "";
    public String $city_id = "";


    public string $invoice_number = "";

    public function mount()
    {
        $this->invoice_number = generateUniqueNumber();
        $this->invoiceData = InvoiceRepository::invoice($this->invoice, $this);

        $this->cities = City::all();
    }

    private function initDepartment()
    {
        $department = (int) $this->department ?? auth()->user()->department_id;

        $this->departments = match ($department) {
            5 => departments(true)->filter(function($item){
                return in_array($item->id, [2,1]);
            })->reverse(),
            4 => departments(true)->filter(function($item){
                return $item->id == 4;
            })->reverse(),
            3 => departments(true)->filter(function($item){
                return in_array($item->id, [3, 1]);
            })->reverse(),
            2 => departments(true)->filter(function($item){
                return in_array($item->id, [2, 1]);
            })->reverse(),
            1 => departments(true)->filter(function($item){
                return $item->id == 1;
            })->reverse(),

        };

        if(isset($this->invoice->id)){
            $this->department_id = department_by_quantity_column($this->invoice->department)->id;
        }

        if($this->department_id == ""){
            $this->selectedDepartment = (array) $this->departments->first();
            $this->department_id =  $this->departments->first()->id;
            $this->d =  $this->selectedDepartment['quantity_column'];
        }else {
            $this->selectedDepartment =  (array) departments(true)->filter(function($item){
                return $item->id == $this->department_id;
            })->first();
            $this->dispatch('departmentChange', ['department'=> $this->selectedDepartment['quantity_column']]);
            $this->d =  $this->selectedDepartment['quantity_column'];
        }
    }

    public function render()
    {
        $this->initDepartment();
        return view('livewire.invoice-and-sales.invoice-form-component');
    }


    public function newCustomer()
    {

        $this->modalTitle = "New";

        $this->saveButton = "Save";

        $this->dispatch("openModal", []);
    }

    public function saveCustomers()
    {
        $this->validate([
            'firstname' =>'required',
            'lastname' => 'required',
            //'phone_number' => 'required|digits_between:11,11|unique:customers,phone_number',
        ]);

        $this->customer = Customer::where('phone_number',  $this->phone_number)->where('status',1)->get()->first();

        if(!$this->customer) {
            $status = $this->save();
            $message = "Customer has been created successfully";
        }else{
            $status = $this->update($this->customer);
            $message = "Customer has been updated successfully";
        }
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

        $customer = new Customer();

        $customer->firstname = $this->firstname;
        $customer->lastname = $this->lastname;
        $customer->email = $this->email;
        $customer->phone_number = $this->phone_number;
        $customer->address = $this->address;

        $customer->save();

        $this->dispatch("newCustomer", ['customer'=>$customer->toArray()]);

        return true;
    }

    public function update(Customer $customer)
    {

        $this->validate([
            'firstname' =>'required',
            'lastname' => 'required',
            'phone_number' => 'required',
        ]);

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

        $this->dispatch("newCustomer", ['customer'=>$customer->toArray()]);

        return true;
    }

    public function generateInvoice()
    {

        $this->initDepartment();

        $this->invoiceData['department'] = $this->d;
        if($this->department == "4") {
            $this->invoiceData['in_department'] = 'retail';
        }else {
            $this->invoiceData['in_department'] = (department_by_id(auth()->user()->department_id)->quantity_column ?? 'wholesales');
        }
        if(!isset($this->invoice->id))
        {
            //$this->invoiceData['invoice_number'] = time();

            $response = null;
            DB::transaction(function() use (&$response){
                $response  = (new invoiceRepository())->createInvoice($this->invoiceData);
            });
        }
        else {
            $response = null;
            DB::transaction(function() use (&$response){
                $response  = (new invoiceRepository())->updateInvoice($this->invoice, $this->invoiceData);
            });
        }

        if(is_array( $response )){

            $this->alert(
                "error",
                "Customer",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' =>"An error occurred in your invoice, please check and try again"
                ]
            );

            return ['errors' =>$response, 'status'=>false];
        }


        $this->alert(
            "success",
            "Invoice",
            [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' =>"Invoice has been generated Successfully!"
            ]
        );


        if($this->department == "4") {
            return redirect()->route('payment.createInvoicePayment', ['invoice_number'=>$response->invoice_number]);
        }else{
            return redirect()->route('invoiceandsales.view',$response->id);
        }

    }

    public function logOutofStockProduct($stock_id) : void
    {
        $logs = OutOfStockLog::firstOrCreate(
            [
                'stock_id' => $stock_id
            ],
            [
                'clicks' => 0,
                'department' => $this->d,
                'user_id' => auth()->id(),
                'stock_id' => $stock_id,
            ]
        );

        $logs->increment('clicks');
        $logs->update([
            'last_click_user_id' => auth()->id(),
            'last_click_date' => now(),
        ]);
    }
}
