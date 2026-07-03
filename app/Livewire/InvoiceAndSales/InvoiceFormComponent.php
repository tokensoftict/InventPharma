<?php

namespace App\Livewire\InvoiceAndSales;

use App\Jobs\AddLogToCustomerLedger;
use App\Models\City;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MemberGroup;
use App\Models\OutOfStockLog;
use App\Models\Prescriber;
use App\Models\ProductNotAvailable;
use App\Models\Stock;
use App\Models\StockOption;
use App\Models\StockOptionValue;
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

    public $departments;

    public string|null $department = NULL;

    public array $invoiceData;

    public string $department_id = "";

    public string $d = "";
    public array $selectedDepartment = [];
    public array $prescribers = [];
    public $cities;
    public $memberGroups;
    private InvoiceRepository $invoiceRepository;
    public string $firstname = "";
    public string $lastname = "";
    public string $address = "";
    public string $phone_number = "";
    public string $email = "";
    public string $city_id = "";
    public string $member_group_id = "";
    public array $selectedProductOption = [];
    public string $selectedProductName = "";
    public array $selectedProductInfo = ["selectedOptions"];

    public string $invoice_number = "";
    public function mount()
    {
        $this->invoice_number = generateUniqueNumber();
        $this->invoiceData = InvoiceRepository::invoice($this->invoice, $this);
        $this->prescribers = Prescriber::query()->select('id', 'name')->where('status', 1)->get()->toArray();
        $this->cities = City::all();
        $this->memberGroups = MemberGroup::all();
    }

    private function initDepartment()
    {
        $department = (int) $this->department ?? auth()->user()->department_id;

        $this->departments = match ($department) {
            5 => (function () {
                    // all this code is just to make wholesales department the first department that will show
                    // if the login user department is Administrative
                    $salesDepartment = departments(true)->filter(
                    fn($item) =>
                    in_array($item->id, [2, 3, 1])
                    );

                    $wholesales = 2;

                    $firstItemKey = $salesDepartment->search(
                    fn($item) => $item->id == $wholesales
                    );

                    if ($firstItemKey !== false) {
                        $itemToMove = $salesDepartment->pull($firstItemKey);
                        $salesDepartment->prepend($itemToMove);
                    }

                    return $salesDepartment;
                })(),
            4 => departments(true)->filter(function ($item) {
                    return $item->id == 4;
                })->reverse(),
            3 => departments(true)->filter(function ($item) {
                    return in_array($item->id, [3, 1]);
                })->reverse(),
            2 => departments(true)->filter(function ($item) {
                    return in_array($item->id, [2, 1]);
                })->reverse(),
            1 => departments(true)->filter(function ($item) {
                    return $item->id == 1;
                })->reverse(),

        };

        if (isset($this->invoice->id)) {
            $this->department_id = department_by_quantity_column($this->invoice->department)->id;
        }

        if ($this->department_id == "") {
            $this->selectedDepartment = (array) $this->departments->first();
            $this->department_id = $this->departments->first()->id;
            $this->d = $this->selectedDepartment['quantity_column'];
        } else {
            $this->selectedDepartment = (array) departments(true)->filter(function ($item) {
                return $item->id == $this->department_id;
            })->first();
            $this->dispatch('departmentChange', ['department' => $this->selectedDepartment['quantity_column']]);
            $this->d = $this->selectedDepartment['quantity_column'];
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
            'firstname' => 'required',
            'lastname' => 'required',
            //'phone_number' => 'required|digits_between:11,11|unique:customers,phone_number',
        ]);

        $this->customer = Customer::where('phone_number', $this->phone_number)->where('status', 1)->get()->first();

        if (!$this->customer) {
            $status = $this->save();
            $message = "Customer has been created successfully";
        } else {
            $status = $this->update($this->customer);
            $message = "Customer has been updated successfully";
        }
        if ($status === true) {
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
            'firstname' => 'required',
            'lastname' => 'required',
            'phone_number' => 'required|digits_between:11,11|unique:customers,phone_number',
        ]);

        $customer = new Customer();

        $customer->firstname = $this->firstname;
        $customer->lastname = $this->lastname;
        $customer->email = $this->email;
        $customer->phone_number = $this->phone_number;
        $customer->address = $this->address;
        $customer->city_id = $this->city_id == "" ? null : $this->city_id;
        $customer->member_group_id = MemberGroup::find(4)?->id ?? null;
        $customer->retail_member_group_id = MemberGroup::find(4)?->id ?? null;

        $customer->save();



        $this->dispatch("newCustomer", ['customer' => $customer->toArray()]);

        return true;
    }

    public function update(Customer $customer)
    {

        $this->validate([
            'firstname' => 'required',
            'lastname' => 'required',
            'phone_number' => 'required',
        ]);

        $customer_ = Customer::where('phone_number', $this->phone_number)->where('status', 1)->get()->first();

        if ($customer_ && $customer_->id != $customer->id) {
            $this->alert(
                "error",
                "Customer",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' => "Customer Phone Number already exists with name " . $customer_->firstname . " " . $customer_->lastname
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
        $customer->member_group_id = 4;
        $customer->retail_member_group_id = 4;


        $customer->save();

        $this->dispatch("newCustomer", ['customer' => $customer->toArray()]);

        return true;
    }

    public function generateInvoice()
    {
        $this->initDepartment();

        $this->invoiceData['department'] = $this->d;
        if ($this->department == "4") {
            $this->invoiceData['in_department'] = 'retail';
        } else {
            $this->invoiceData['in_department'] = (department_by_id(auth()->user()->department_id)->quantity_column ?? 'wholesales');
        }
        if (!isset($this->invoice->id)) {
            //$this->invoiceData['invoice_number'] = time();

            $response = null;
            DB::transaction(function () use (&$response) {
                $response = (new invoiceRepository())->createInvoice($this->invoiceData);
            });
        } else {
            $response = null;
            DB::transaction(function () use (&$response) {
                $response = (new invoiceRepository())->updateInvoice($this->invoice, $this->invoiceData);
            });
        }

        if (is_array($response)) {

            $this->alert(
                "error",
                "Customer",
                [
                    'position' => 'center',
                    'timer' => 2000,
                    'toast' => false,
                    'text' => "An error occurred in your invoice, please check and try again"
                ]
            );

            return ['errors' => $response, 'status' => false];
        }


        $this->alert(
            "success",
            "Invoice",
            [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' => "Invoice has been generated Successfully!"
            ]
        );


        if ($this->department == "4") {
            return redirect()->route('payment.createInvoicePayment', ['invoice_number' => $response->invoice_number]);
        } else {
            return redirect()->route('invoiceandsales.view', $response->id);
        }

    }

    public function logOutofStockProduct($stock_id): void
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


    public function logProductNotExist($name): bool
    {
        $log = ProductNotAvailable::updateOrCreate(
            ['name' => $name],
            [
                'department' => $this->d,
                'user_id' => auth()->id(),
                'date_time' => now()
            ]
        );

        return true;
    }


    public function parseCustomerFromQrCode($code)
    {
        $decryptCode = false;
        try {
            $decryptCode = decrypt($code);
        } catch (\Exception $e) {
            return ['status' => false, "message" => ""];
        }

        $customer = json_decode($decryptCode, true);
        if (is_array($customer)) {
            $customer = Customer::where('phone_number', $customer['phone'])->first();
            if (!$customer) {
                $customer = json_decode($decryptCode, true);
                $newCustomer = new Customer();
                $newCustomer->phone_number = $customer['phone'];
                $newCustomer->firstname = $customer['first_name'];
                $newCustomer->lastname = $customer['first_name'];
                $newCustomer->email = $customer['email'];
                $newCustomer->retail_customer = (auth()->user()->department_id === 4 ? 1 : 0);
                $newCustomer->city_id = NULL;
                $newCustomer->member_group_id = 4;
                $newCustomer->retail_member_group_id = 4;
                $newCustomer->save();
                $newCustomer->fresh();
                $customer = $newCustomer->fresh();
            }

            $discount = 0;
            $department = (int)$this->department ?? auth()->user()->department_id;
            $group = null;
            if ($department === 4) {
                $group = $customer->retail_member_group;

                if ($group && $group->member_discount > 0) {
                    if (is_null($group->discount_until) || \Carbon\Carbon::parse($group->discount_until)->isFuture()) {
                        $discount = $group->member_discount;
                    }
                }
            }

            return [
                'status' => true,
                'customer' => $customer->toArray(),
                'membership_discount' => $discount,
                'membership_label' => ($group && $discount > 0) ? $group->name : ''
            ];

        } else {
            return ["status" => false, "message" => "Could not decrypt customer information"];
        }
    }


    public function triggerProductOptionModal(array $selectedProduct)
    {
        $stock = Stock::find($selectedProduct['id']);
        $this->selectedProductOption = $stock->buildProductOptions($this->d)->toArray();
        $this->selectedProductName = $stock->name;
        $this->selectedProductInfo = $selectedProduct;
        foreach ($this->selectedProductOption as $option) {
            $this->selectedProductInfo['selectedOptions'][$option['option_id']] = "";
        }

        $this->dispatch('openProductOptionModal', ["selectedProduct" => $selectedProduct]);
    }

    public function saveProductOption()
    {
        $options = $this->selectedProductInfo['selectedOptions'];

        $formatOptions = [];
        foreach ($options as $key => $option) {
            $optionInfo = StockOptionValue::with(['stock_option.option_field', 'stock_option.option_field.option', 'option_field_value'])->find($option);

            $formatOptions[] = [
                "option_name" => $optionInfo->stock_option->option_field->name,
                "selectedValue" => $optionInfo->option_field_value->name,
                "amount" => $optionInfo->{$this->d == "retail" ? "retail_price" : "wholesales_price"},
                "sign" => $optionInfo->{$this->d == "retail" ? "retail_price_prefix" : "wholesales_price_prefix"}
            ];
        }
        $this->selectedProductInfo['selectedOptions'] = $formatOptions;
        $this->dispatch('closeProductOptionModal', ["selectedProduct" => $this->selectedProductInfo]);
    }
}
